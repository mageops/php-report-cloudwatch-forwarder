<?php

namespace CS\ExceptionReportAwsLogger;

use Aws\CloudWatch\CloudWatchClient;
use Aws\CloudWatchLogs\Exception\CloudWatchLogsException;
use Aws\Exception\AwsException;
use Aws\Sdk;
use Psr\Log\LoggerInterface;

class CloudWatchPusher
{
    /**
     * Maximum number of retries when AWS reports throttling.
     */
    const ERROR_RETRIES = 10;

    /**
     * Delay between throttling retries in seconds.
     */
    const THROTTLING_DELAY = 20;

    /**
     * @var Sdk
     */
    private $aws;

    /**
     * @var CloudWatchClient
     */
    private $client;

    /**
     * Array of entries to be sent.
     *
     * Each item shall be an array with two keys:
     *   - content - string
     *   - timestamp - int
     *
     * @var array
     */
    private $buffer = [];

    /**
     * @var LoggerInterface
     */
    private $debugLogger;

    /**
     * @var string
     */
    private $groupName;

    /**
     * @var string
     */
    private $streamName;

    /**
     * @var string
     */
    private $sequenceToken;

    /**
     * Max size of buffer before being flushed.
     *
     * @var int
     */
    private $chunkSize;

    /**
     * Current buffer size in bytes.
     *
     * @var int
     */
    private $bufferSize = 0;

    /**
     * @param string $groupName
     * @param string $streamName
     * @param string $region
     * @param string $version
     * @param int $chunkSize
     * @param LoggerInterface $debugLogger
     */
    public function __construct(
        $groupName,
        $streamName,
        $region = null,
        $version = 'latest',
        $chunkSize = 100000,
        LoggerInterface $debugLogger
    ) {
        $awsConfig = [
            'version' => $version,
        ];

        if (null !== $region) {
            $awsConfig['region'] = $region;
        }

        $this->aws = new Sdk($awsConfig);

        $this->groupName = $groupName;
        $this->streamName = $streamName;

        $this->client = $this->aws->createCloudWatchLogs();
        $this->debugLogger = $debugLogger;
        $this->chunkSize = $chunkSize;

        $this->init();
    }

    /**
     * @param string $groupName
     * @return array|null
     */
    private function getLogGroup($groupName)
    {
        $groupsDescription = $this->client->describeLogGroups([
            'logGroupNamePrefix' => $groupName
        ]);

        foreach ($groupsDescription->get('logGroups') as $group) {
            if ($group['logGroupName'] === $groupName) {
                return $group;
            }
        }

        return null;
    }


    /**
     * @param string $groupName
     */
    private function ensureLogGroupExists($groupName)
    {
        $group = $this->getLogGroup($groupName);

        if (!$group) {
            $this->debugLogger->warning(sprintf('Group "%s" does not exist', $groupName));

            $this->client->createLogGroup([
                'logGroupName' => $groupName
            ]);

            $this->debugLogger->info(sprintf('Created "%s" group', $groupName));
        }
    }

    /**
     * @param $groupName
     * @param $streamName
     * @return array|null
     */
    private function getStream($groupName, $streamName)
    {
        $streamsDescription = $this->client->describeLogStreams([
            'logGroupName' => $groupName,
            'logStreamNamePrefix' => $streamName,
        ]);

        foreach ($streamsDescription->get('logStreams') as $stream) {
            if ($stream['logStreamName'] === $streamName) {
                return $stream;
            }
        }

        return null;
    }

    /**
     * @param string $groupName
     * @param string $streamName
     */
    private function ensureStreamExists($groupName, $streamName)
    {
        $stream = $this->getStream($groupName, $streamName);

        if (!$stream) {
            $this->debugLogger->warning(sprintf('Stream "%s" does not exist in group "%s"', $streamName, $groupName));

            $this->client->createLogStream([
                'logGroupName' => $groupName,
                'logStreamName' => $streamName,
            ]);

            $this->debugLogger->info(sprintf('Created "%s" stream in group "%s"', $streamName, $groupName));
        }
    }

    /**
     * @param string $groupName
     * @param string $streamName
     * @return string|null
     */
    private function getSequenceToken($groupName, $streamName)
    {
        $stream = $this->getStream($groupName, $streamName);

        if (isset($stream['uploadSequenceToken'])) {
            return $stream['uploadSequenceToken'];
        }

        return null;
    }

    /**
     * Initialies the log group and stream.
     */
    private function init()
    {
        $this->ensureLogGroupExists($this->groupName);
        $this->ensureStreamExists($this->groupName, $this->streamName);
        $this->sequenceToken = $this->getSequenceToken($this->groupName, $this->streamName);
    }

    /**
     * Transforms log entry into
     * @param array $entry
     * @return array
     */
    private function transformEntry(array $entry)
    {
        return [
            'message' => $entry['content'],
            'timestamp' => $entry['timestamp'] * 1000,
        ];
    }

    /**
     * @param AwsException $exception
     * @return array
     */
    private function getExceptionResponseData(AwsException $exception)
    {
        $exception->getResponse()->getBody()->rewind();
        $contents = $exception->getResponse()->getBody()->getContents();

        return json_decode($contents, true);
    }

    /**
     * Writes log entries to AWS buffer.
     *
     * {@inheritdoc}
     */
    private function write(array $entries)
    {
        $data = [
            'logGroupName' => $this->groupName,
            'logStreamName' => $this->streamName,
            'logEvents' => $entries
        ];

        if (!empty($this->sequenceToken)) {
            $data['sequenceToken'] = $this->sequenceToken;
        }

        $response = null;
        $throttled = true;
        $retries = 0;

        while ($throttled && $retries < self::ERROR_RETRIES) {
            try {
                $response = $this->client->putLogEvents($data);
            } catch (CloudWatchLogsException $exception) {
                if ($exception->getAwsErrorCode() === 'ThrottlingException') {
                    $retries++;

                    $this->debugLogger->warning(sprintf('Rate exceeded when pushing to "%s:%s", waiting for %d seconds and starting %d retry',
                        $this->groupName,
                        $this->streamName,
                        self::THROTTLING_DELAY,
                        $retries
                    ));

                    sleep(self::THROTTLING_DELAY);

                    continue;
                } elseif ($exception->getAwsErrorCode() === 'DataAlreadyAcceptedException') {
                    $responseData = $this->getExceptionResponseData($exception);
                    $this->sequenceToken = $responseData['expectedSequenceToken'];

                    $this->debugLogger->warning(sprintf('Data was already pushed to "%s:%s", skipping batch.',
                        $this->groupName,
                        $this->streamName,
                        $retries
                    ));

                    return;
                } elseif ($exception->getAwsErrorCode() === 'InvalidSequenceTokenException') {
                    $responseData = $this->getExceptionResponseData($exception);
                    $this->sequenceToken = $responseData['expectedSequenceToken'];
                    $retries++;

                    $this->debugLogger->warning(sprintf('Invalid sequence token when pushing to "%s:%s", updating and starting %d retry',
                        $this->groupName,
                        $this->streamName,
                        $retries
                    ));

                    continue;
                } else {
                    throw $exception;
                }
            }

            $throttled = false;
        }

        if ($throttled) {
            $this->debugLogger->critical(sprintf('Exceeded %d retry limit when pushing to "%s:%s"',
                self::ERROR_RETRIES,
                $this->groupName,
                $this->streamName
            ));

            throw new \RuntimeException('Error retry limit exceeded');
        }

        $this->sequenceToken = $response->get('nextSequenceToken');

        $this->debugLogger->info(sprintf('Written %d entries into "%s:%s"',
            count($entries),
            $this->groupName,
            $this->streamName
        ));

        if (isset($response['rejectedLogEventsInfo']['tooOldLogEventEndIndex'])) {
            $this->debugLogger->warning(sprintf('Stream "%s:%s" rejected %d events because they are too old',
                $this->groupName,
                $this->streamName,
                $response['rejectedLogEventsInfo']['tooOldLogEventEndIndex']
            ));
        }
    }

    /**
     * Batches the buffer into chunks each one spanning less than 24hrs.
     *
     * Upstream does not accept a single chunk which spans more.
     *
     * @param array $buffer
     * @return array
     */
    private function batch(array $buffer)
    {
        usort($buffer, function($a, $b) {
            return $a['timestamp'] - $b['timestamp'];
        });

        $chunks = [];
        $chunk = [];
        $lastTimestamp = 0;

        foreach ($buffer as $entry) {
            if (($entry['timestamp'] - $lastTimestamp) > (24 * 3600)) {
                if (!empty($chunk)) {
                    $chunks[] = $chunk;
                }

                $chunk = [];
            }

            $chunk[] = $entry;

            $lastTimestamp = $entry['timestamp'];
        }

        if (!empty($chunk)) {
            $chunks[] = $chunk;
        }

        return $chunks;
    }

    /**
     * Writes the buffer to AWS sink.
     */
    public function flush()
    {
        if (empty($this->buffer)) {
            return;
        }

        foreach ($this->batch($this->buffer) as $chunk) {
            $this->write(array_map([$this, 'transformEntry'], $chunk));
        }

        $this->buffer = [];
        $this->bufferSize = 0;
    }

    /**
     * @param string $message
     * @param int $timestamp
     */
    public function push($message, $timestamp)
    {
        $this->buffer[] = [
            'content' => $message,
            'timestamp' => $timestamp,
        ];

        $this->bufferSize += mb_strlen($message);

        if ($this->bufferSize >= $this->chunkSize) {
            $this->flush();
        }
    }

    public function __destruct()
    {
        $this->flush();
    }
}