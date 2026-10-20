<?php

namespace Runa\Console\Commands\Database;

use Illuminate\Database\Console\Migrations\ResetCommand;

final class GuardedResetCommand extends ResetCommand
{
    use GuardsTestDatabase;
}
