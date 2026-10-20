<?php

use Illuminate\Console\Command;
use Runa\Management\BackupCommand;
use Runa\Management\DeployCommand;
use Runa\Management\HostCommand;
use Runa\Management\IntegrityCommand;
use Runa\Management\ProductionCommand;

require_once __DIR__.'/autoload.php';

$commands = [
    ProductionCommand::class,
    DeployCommand::class,
    BackupCommand::class,
    HostCommand::class,
    IntegrityCommand::class,
];

if (is_file(__DIR__.'/development.php')) {
    /** @var list<class-string<Command>> $development */
    $development = require __DIR__.'/development.php';
    $commands = [...$commands, ...$development];
}

// Owner orchestration is separately delivered; customer installation receipts
// disable it even if an excluded source directory is accidentally restored.
$owner = dirname(__DIR__, 3).'/distribution/maintenance/commands.php';
if (! is_file($owner)) {
    $owner = dirname(__DIR__, 2).'/distribution/maintenance/commands.php';
}
if (is_file($owner) && ! is_file(dirname($owner, 3).'/.git/runa-bootstrap.json')) {
    /** @var list<class-string<Command>> $maintenance */
    $maintenance = require $owner;
    $commands = [...$commands, ...$maintenance];
}

return $commands;
