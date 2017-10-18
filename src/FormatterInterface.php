<?php

namespace CS\ExceptionReportAwsLogger;

interface FormatterInterface
{
    /**
     * @param string $filename
     * @param string $message
     * @param int $timestamp
     * @return string
     */
    public function format($filename, $message, $timestamp);
}