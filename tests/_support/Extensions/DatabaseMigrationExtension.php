<?php

namespace Extensions;

use Codeception\Events;
use Codeception\Extension;
use Codeception\Module\Cli;

class DatabaseMigrationExtension extends Extension
{
    public static $events = [
        Events::SUITE_BEFORE => 'beforeSuite',
    ];

    public function beforeSuite()
    {
        /** @var Cli $cli */
        $cli = $this->getModule('Cli');
        $alias = __DIR__ . '/../../_app/yii';
        $cli->runShellCommand("php $alias migrate/up --interactive=0");
        $cli->runShellCommand("php $alias migrate/up --migrationPath=./migrations --interactive=0");
    }
}