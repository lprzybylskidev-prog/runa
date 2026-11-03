<?php

namespace Tests;

use Runa\Contracts\Modules\PreparationParticipant;
use Runa\Database\RequiredSeeds;
use Runa\ModuleFramework\LifecyclePolicies;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleHealth;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\Updates\ModuleUpdates;
use Symfony\Component\Process\Process;

abstract class PlatformTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Explicit owner candidate runtime, not a change to released platform VERSION.
        // Private isolated-module fixtures may replace these with their own runtimes.
        $this->app->singleton(ModuleAvailability::class, fn () => new ModuleAvailability(
            app(ModuleRegistry::class), app(ModuleHealth::class), app(LifecyclePolicies::class), '0.7.0',
        ));
        $this->app->singleton(ModuleUpdates::class, fn () => new ModuleUpdates(
            app('db')->connection(), app(ModuleRegistry::class), base_path(), '0.7.0', true, app(PreparationParticipant::class),
        ));
    }

    protected function migrateTestDatabase(string $name, string $storage): void
    {
        $platform = new Process([PHP_BINARY, realpath(__DIR__.'/../../..').'/distribution/platform-database.php', 'migrate', '--force', '--no-interaction'], base_path(), ['APP_ENV' => 'testing', 'DB_DATABASE' => $name, 'LARAVEL_STORAGE_PATH' => $storage]);
        $platform->setTimeout(null);
        $platform->mustRun();
        RequiredSeeds::run(app(), str_repeat('0', 40));
        parent::migrateTestDatabase($name, $storage);
    }
}
