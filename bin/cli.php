#!/usr/bin/env php
<?php

/* Must run before the autoloader — the dependencies require PHP 8.1
 * and would die with an obscure fatal error on older interpreters. */
if (PHP_VERSION_ID < 80100) {
    fwrite(STDERR, sprintf(
        "%s requires PHP >= 8.1, you are running PHP %s (%s).\n",
        basename($argv[0]),
        PHP_VERSION,
        PHP_BINARY
    ));

    exit(70);
}

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/version')) {
    define('VERSION', file_get_contents(__DIR__ . '/version'));
} else {
    define('VERSION', 'rUNKNOWN');
}

use CS\ExceptionReportAwsLogger\App;

$app = new App();
$app->run();