<?php

namespace CS\ExceptionReportAwsLogger;

class DefaultFormatter implements FormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function format($filename, $message, $timestamp)
    {
        return sprintf("Filename: %s\nDirectory: %s\nModified: %s\n--------------------------\n%s",
            basename($filename),
            dirname($filename),
            date_create('@' . $timestamp)->format('d.m.Y H:i:s'),
            $message
        );
    }
}