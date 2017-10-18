<?php

namespace CS\ExceptionReportAwsLogger;

use Psr\Log\LoggerInterface;

class DirectoryMonitor
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $stateDir;

    /**
     * @var
     */
    private $lastScanned;

    /**
     * @var null|string
     */
    private $glob;

    /**
     * @var string
     */
    private $pathExpression;

    /**
     * @var LoggerInterface
     */
    private $debugLogger;

    /**
     * @param string $path
     * @param string $stateDir
     * @param string|null $glob Glob wildcard expression for filtering files
     * @param LoggerInterface $debugLogger
     */
    public function __construct(
        $path,
        $stateDir,
        $glob = '*',
        LoggerInterface $debugLogger
    ) {
        $this->path = realpath($path);
        $this->glob = $glob;
        $this->pathExpression = $this->path . '/' . $this->glob;
        $this->stateDir = $stateDir;

        $this->restoreState();
        $this->debugLogger = $debugLogger;
    }

    /**
     * @return string
     */
    private function getStateFile()
    {
        return $this->stateDir . '/' . md5($this->pathExpression);
    }

    /**
     * Restores last scan time.
     */
    private function restoreState()
    {
        if (file_exists($this->getStateFile())) {
            list($this->lastScanned) = unserialize(file_get_contents($this->getStateFile()));
        }
    }

    /**
     * Saves the last scan time so only new files are returned next time.
     */
    public function saveState()
    {
        file_put_contents($this->getStateFile(), serialize([$this->lastScanned]));
    }

    /**
     * Scans the directory and returns new items.
     *
     * @return array
     */
    public function scan()
    {
        $this->debugLogger->debug(sprintf('Scanning "%s" which was last scanned: %s',
            $this->pathExpression,
            $this->lastScanned ? date_create("@{$this->lastScanned}")->format('d.m.Y H:i:s') : 'never'
        ));

        $items = [];

        foreach (glob($this->pathExpression) as $filename) {
            if (!is_file($filename)) {
                continue;
            }

            $mtime = filemtime($filename);

            if ($this->lastScanned && $mtime < $this->lastScanned) {
                continue;
            }

            $items[] = [
                'filename' => $filename,
                'dirname' => dirname($filename),
                'basename' => basename($filename),
                'mtime' => $mtime,
                'content' => file_get_contents($filename),
            ];
        }

        $this->lastScanned = time();

        $this->debugLogger->info(sprintf('Scanned "%s" finding %d new files',
            $this->pathExpression,
            count($items)
        ));

        return $items;
    }
}