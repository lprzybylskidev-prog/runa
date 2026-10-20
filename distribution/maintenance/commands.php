<?php

use Runa\Management\HookCommand;
use Runa\Management\InstallCommand;
use Runa\Management\ProjectCommand;

require_once __DIR__.'/InstallCommand.php';
require_once __DIR__.'/ProjectCommand.php';
require_once __DIR__.'/HookCommand.php';

return [InstallCommand::class, ProjectCommand::class, HookCommand::class];
