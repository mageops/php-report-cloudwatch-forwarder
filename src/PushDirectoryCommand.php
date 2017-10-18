<?php

namespace CS\ExceptionReportAwsLogger;

use Aws\CloudWatchLogs\Exception\CloudWatchLogsException;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class PushDirectoryCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('push:directory')
            ->setDescription('Pushes files from a directory')
            ->addArgument('directory', InputArgument::REQUIRED, 'Directory to scan for files (accepts glob format)')
            ->addArgument('glob', InputArgument::OPTIONAL, 'Glob expressiong for filtering files (e.g. *.log)', '*')
            ->addOption('group', 'g', InputOption::VALUE_REQUIRED, 'Name of the LogGroup', 'exception-reports')
            ->addOption('stream', 's', InputOption::VALUE_REQUIRED, 'Name of the LogStream', gethostname())
            ->addOption('region', 'r', InputOption::VALUE_REQUIRED, 'Id of the AWS region', null)
            ->addOption('aws-version', 'av', InputOption::VALUE_REQUIRED, 'Version of AWS API', 'latest')
            ->addOption('state-dir', 't', InputOption::VALUE_REQUIRED, 'Path to directory where last states are kept', $this->getDefaultStateDirectoryPath())
            ->addOption('chunk-size', 'c', InputOption::VALUE_REQUIRED, 'Max buffer size before flush', 200000)
        ;
    }


    /**
     * @param string $dir
     * @return bool
     */
    protected function tryStateDirectory($dir)
    {
        $topDir = dirname($dir);

        if (is_dir($topDir) && is_writable($topDir)) {
            if (!file_exists($dir)) {
                mkdir($dir, 0770, true);
            }

            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    protected function getDefaultStateDirectoryPath()
    {
        $stateDirs = [
            '/var/spool/exclog-state',
            $_SERVER['HOME'] . '/.exclog-state',
            sys_get_temp_dir() . '/exclog-state',
        ];

        foreach ($stateDirs as $dir) {
            if ($this->tryStateDirectory($dir)) {
                return $dir;
            }
        }

        throw new \RuntimeException(sprintf('Could not find a suitable state directory'));
    }

    /**
     * @param OutputInterface $output
     * @return ConsoleLogger
     */
    private function createLogger(OutputInterface $output)
    {
        return new ConsoleLogger($output, [
            LogLevel::DEBUG => OutputInterface::VERBOSITY_VERBOSE,
            LogLevel::NOTICE => OutputInterface::VERBOSITY_VERBOSE,
            LogLevel::WARNING => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::ALERT => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::ERROR => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::CRITICAL => OutputInterface::VERBOSITY_QUIET,
        ]);
    }

    /**
     * @param array $item
     * @return string
     */
    protected function formatItem($item)
    {
        return sprintf("Filename: %s\nDirectory: %s\n--------------------------\n%s",
            $item['basename'],
            $item['dirname'],
            $item['content']
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->createLogger($output);

        $monitor = new DirectoryMonitor(
            $input->getArgument('directory'),
            $input->getOption('state-dir'),
            $input->getArgument('glob'),
            $logger
        );

        $pusher = new CloudWatchPusher(
            $input->getOption('group'),
            $input->getOption('stream'),
            $input->getOption('region'),
            $input->getOption('aws-version'),
            $input->getOption('chunk-size'),
            $logger
        );

        foreach ($monitor->scan() as $item) {
            $pusher->push(
                $this->formatItem($item),
                $item['mtime']
            );

            $logger->notice(sprintf('Pushed file "%s"', $item['filename']));
        }

        /* Save the state only after successful push */
        $monitor->saveState();

        $pusher->flush();
    }
}