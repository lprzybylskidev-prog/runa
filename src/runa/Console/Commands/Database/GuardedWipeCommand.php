<?php

namespace Runa\Console\Commands\Database;

use Illuminate\Database\Console\WipeCommand;
use Illuminate\Database\DatabaseManager;
use Runa\Database\DatabaseScope;

final class GuardedWipeCommand extends WipeCommand
{
    use GuardsTestDatabase;

    protected function dropAllTables($database): void
    {
        $this->laravel->make(DatabaseScope::class)->wipe($this->laravel->make(DatabaseManager::class)->connection($database));
    }
}
