<?php

namespace CS\ExceptionReportAwsLogger;

class SerializedArrayFormatter extends DefaultFormatter
{
    /**
     * {@inheritdoc}
     */
    public function format($filename, $message, $timestamp)
    {
        return parent::format($filename, $this->formatMessage($message), $timestamp);
    }

    /**
     * {@inheritdoc}
     */
    public function formatMessage($message)
    {
        $data = @unserialize($message);

        if (false === $data) {
            return $message;
        }

        if (!is_array($data)) {
            return $message;
        }

        $result = '';

        foreach ($data as $key => $value) {
            $result .= "\n" . $key . "\n";
            $result .= str_repeat('-', strlen($key)) . "\n";

            if (is_scalar($value)) {
                $result .= $value;
            } else {
                $result .= print_r($value, true);
            }

            $result .= "\n";
        }

        return $result;
    }
}