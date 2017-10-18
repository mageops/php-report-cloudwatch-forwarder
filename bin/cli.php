#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use CS\ExceptionReportAwsLogger\App;

$app = new App();
$app->run();