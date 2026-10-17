<?php

namespace App\Console\Commands\Database;

use Illuminate\Database\Console\Migrations\RollbackCommand;

final class GuardedRollbackCommand extends RollbackCommand
{
    use GuardsTestDatabase;
}
