#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/version')) {
    define('VERSION', 'r' . file_get_contents(__DIR__ . '/version'));
} else {
    define('VERSION', 'r[local]');
}

use CS\ExceptionReportAwsLogger\App;

$app = new App();
$app->run();