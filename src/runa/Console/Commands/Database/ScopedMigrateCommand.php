<?php

namespace Runa\Console\Commands\Database;

use Illuminate\Database\Console\Migrations\MigrateCommand;

final class ScopedMigrateCommand extends MigrateCommand
{
    use ScopesDatabase;

    protected function loadSchemaState(): void
    {
        // A native whole-database schema dump cannot establish either owner's boundary.
        if (glob(database_path('schema/*.sql')) !== []) {
            throw new \RuntimeException('Whole-database schema restores are not supported by scoped migrations.');
        }
    }
}
