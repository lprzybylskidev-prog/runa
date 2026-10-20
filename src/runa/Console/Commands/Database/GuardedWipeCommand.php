<?php

namespace Runa\Console\Commands\Database;

use Illuminate\Database\Console\WipeCommand;

final class GuardedWipeCommand extends WipeCommand
{
    use GuardsTestDatabase;
}
