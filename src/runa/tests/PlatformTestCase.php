<?php

namespace Tests;

use Symfony\Component\Process\Process;

abstract class PlatformTestCase extends TestCase
{
    protected function migrateTestDatabase(string $name, string $storage): void
    {
        $platform = new Process([PHP_BINARY, dirname(__DIR__, 3).'/distribution/platform-database.php', 'migrate', '--force', '--no-interaction'], base_path(), ['APP_ENV' => 'testing', 'DB_DATABASE' => $name, 'LARAVEL_STORAGE_PATH' => $storage]);
        $platform->setTimeout(null);
        $platform->mustRun();
        parent::migrateTestDatabase($name, $storage);
    }
}
