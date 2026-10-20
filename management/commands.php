<?php

use Runa\Management\DevelopmentCommand;

// Shared by normal Artisan and its runtime-independent management bootstrap.
require_once __DIR__.'/DevelopmentCommand.php';

return [DevelopmentCommand::class];
