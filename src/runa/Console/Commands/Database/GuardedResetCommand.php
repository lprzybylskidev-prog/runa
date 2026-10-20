<?php

namespace Runa\Console\Commands\Database;

use Illuminate\Database\Console\Migrations\ResetCommand;
use Runa\Database\DatabaseScope;

final class GuardedResetCommand extends ResetCommand
{
    use GuardsTestDatabase;

    /** @return list<string> */
    protected function getMigrationPaths(): array
    {
        return $this->laravel->make(DatabaseScope::class)->paths($this->input);
    }
}
