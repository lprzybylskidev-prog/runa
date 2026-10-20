<?php

namespace Runa\Console\Commands\Database;

use Illuminate\Database\Console\Migrations\RollbackCommand;
use Runa\Database\DatabaseScope;

final class GuardedRollbackCommand extends RollbackCommand
{
    use GuardsTestDatabase;

    /** @return list<string> */
    protected function getMigrationPaths(): array
    {
        return $this->laravel->make(DatabaseScope::class)->paths($this->input);
    }
}
