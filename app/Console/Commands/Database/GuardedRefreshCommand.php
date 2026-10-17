<?php

namespace App\Console\Commands\Database;

use Illuminate\Database\Console\Migrations\RefreshCommand;

final class GuardedRefreshCommand extends RefreshCommand
{
    use GuardsTestDatabase;
}
