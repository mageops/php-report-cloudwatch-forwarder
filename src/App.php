<?php

namespace CS\ExceptionReportAwsLogger;

use Symfony\Component\Console\Application as BaseApp;

class App extends BaseApp
{
    public function __construct()
    {
        parent::__construct('Exception AWS Logger', 'v1.0');

        $this->add(new PushDirectoryCommand());
        $this->setDefaultCommand('list');
    }
}