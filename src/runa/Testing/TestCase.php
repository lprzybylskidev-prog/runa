<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Testing\TestCase as FrameworkTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\ParallelTesting;
use Runa\Testing\Database\TestDatabaseGuard;
use RuntimeException;
use Symfony\Component\Process\Process;

abstract class TestCase extends FrameworkTestCase
{
    /** @var array<string, true> */
    private static array $migrated = [];

    protected function tearDown(): void
    {
        $database = $this->app?->make(DatabaseManager::class);
        try {
            // Let transaction/other framework teardown callbacks finish first.
            parent::tearDown();
        } finally {
            foreach ($database?->getConnections() ?? [] as $connection) {
                $connection->disconnect();
            }
        }
    }

    public function createApplication(): Application
    {
        $token = getenv('TEST_TOKEN') ?: '0';
        if (! preg_match('/\A(?:0|[1-9][0-9]{0,4})\z/D', $token)) {
            throw new RuntimeException('Test worker token must be a canonical integer from 0 to 99999.');
        }
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $storage = dirname(__DIR__, 3).'/.runtime/tests/worker-'.$token;
        foreach (['framework/cache/data', 'framework/sessions', 'framework/views', 'logs', 'app/private'] as $directory) {
            if (! is_dir($storage.'/'.$directory)) {
                mkdir($storage.'/'.$directory, 0775, true);
            }
        }
        $app->useStoragePath($storage);
        $name = 'runa_test'.($token === '0' ? '' : '_test_'.$token);
        // Select the worker before provider boot captures a database connection in services.
        $app->beforeBootstrapping(BootProviders::class, function () use ($app, $token, $name): void {
            // This base owns guarded worker selection; Laravel must not suffix it again.
            $app->make(ParallelTesting::class)->resolveOptionsUsing(static fn (string $option) => $option === 'without_databases' ? true : ($_SERVER['LARAVEL_PARALLEL_TESTING_'.strtoupper($option)] ?? false));
            $guard = $app->make(TestDatabaseGuard::class);
            $guard->assertSafe();
            // Validate the base identity before any worker database DDL; tokens cannot inject SQL.
            if ($token !== '0') {
                if (! DB::selectOne('SELECT 1 FROM pg_database WHERE datname = ?', [$name])) {
                    DB::statement('CREATE DATABASE "'.$name.'"');
                }
                config()->set('database.connections.pgsql.database', $name);
                DB::purge('pgsql');
            }
            $guard->assertSafe();
            config()->set('cache.default', 'array');
            config()->set('cache.prefix', 'runa_test_'.$token.'_');
            config()->set('queue.default', 'sync');
            config()->set('mail.default', 'array');
        });
        $app->make(Kernel::class)->bootstrap();
        if (! isset(self::$migrated[$name])) {
            $this->migrateTestDatabase($name, $storage);
            self::$migrated[$name] = true;
        }

        return $app;
    }

    protected function migrateTestDatabase(string $name, string $storage): void
    {
        if ($name !== 'runa_test' && ! Schema::hasTable('shared.migrations')) {
            // Fresh customer workers use the delivered forward-only primitive, never owner tools.
            $revision = new Process(['git', 'rev-parse', '--verify', 'HEAD'], dirname(base_path()));
            $revision->mustRun();
            $prepare = new Process([PHP_BINARY, base_path('runa/database/prepare.php'), trim($revision->getOutput())], base_path(), [
                'APP_ENV' => 'testing', 'DB_DATABASE' => $name, 'LARAVEL_STORAGE_PATH' => $storage, 'TEST_TOKEN' => false,
            ]);
            $prepare->setTimeout(null);
            $prepare->mustRun();
        }
        if (Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]) !== 0) {
            throw new RuntimeException('Application test database migration failed.');
        }
    }
}
