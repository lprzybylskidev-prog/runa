<?php

namespace Runa\Console\Commands\Database;

use Illuminate\Database\Console\Migrations\StatusCommand;

final class ScopedStatusCommand extends StatusCommand
{
    use ScopesDatabase;
}
