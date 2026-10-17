<?php

namespace App\Console\Commands\Database;

use Illuminate\Database\Console\Migrations\FreshCommand;

final class GuardedFreshCommand extends FreshCommand
{
    use GuardsTestDatabase;
}
