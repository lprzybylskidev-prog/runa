<?php

use Runa\Management\HookCommand;
use Runa\Management\InstallCommand;

require_once __DIR__.'/InstallCommand.php';
require_once __DIR__.'/HookCommand.php';

return [InstallCommand::class, HookCommand::class];
