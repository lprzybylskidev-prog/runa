<?php

namespace App\Console\Commands;

use App\Support\TestDatabaseGuard;
use Illuminate\Console\Command;

final class CheckTestDatabase extends Command
{
    protected $signature = 'runa:test-db:guard';

    protected $description = 'Verify configured and actual isolated test database identity';

    public function handle(TestDatabaseGuard $guard): int
    {
        $guard->assertSafe();
        $this->info('Test database guard passed.');

        return self::SUCCESS;
    }
}
