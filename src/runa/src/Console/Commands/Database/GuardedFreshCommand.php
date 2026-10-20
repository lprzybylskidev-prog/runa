<?php

namespace Runa\Console\Commands\Database;

use Illuminate\Database\Console\Migrations\FreshCommand;

final class GuardedFreshCommand extends FreshCommand
{
    use GuardsTestDatabase;
}
