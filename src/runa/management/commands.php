<?php

use Illuminate\Console\Command;
use Runa\Management\BackupCommand;
use Runa\Management\DeployCommand;
use Runa\Management\InstallCommand;
use Runa\Management\ProductionCommand;

require_once __DIR__.'/autoload.php';

$commands = [
    ProductionCommand::class,
    DeployCommand::class,
    BackupCommand::class,
    InstallCommand::class,
];

if (is_file(__DIR__.'/development.php')) {
    /** @var list<class-string<Command>> $development */
    $development = require __DIR__.'/development.php';
    $commands = [...$commands, ...$development];
}

return $commands;
