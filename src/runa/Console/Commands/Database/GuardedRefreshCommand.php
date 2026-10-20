<?php

namespace Runa\Console\Commands\Database;

use Illuminate\Database\Console\Migrations\RefreshCommand;

final class GuardedRefreshCommand extends RefreshCommand
{
    use GuardsTestDatabase;

    protected function runSeeder($database): void
    {
        $result = $this->call('db:seed', array_filter(['--database' => $database, '--force' => true, '--class' => $this->option('seeder')]));
        if ($result !== 0) {
            throw new \RuntimeException('Scoped refresh seeding failed.');
        }
    }
}
