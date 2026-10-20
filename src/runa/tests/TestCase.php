<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as FrameworkTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Runa\Support\TestDatabaseGuard;
use RuntimeException;

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
        $app->make(Kernel::class)->bootstrap();
        $guard = $app->make(TestDatabaseGuard::class);
        $guard->assertSafe();
        // Validate the base identity before any worker database DDL; tokens cannot inject SQL.
        $name = 'runa_test'.($token === '0' ? '' : '_test_'.$token);
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
        if (! isset(self::$migrated[$name])) {
            if (Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]) !== 0) {
                throw new RuntimeException('Test database migration failed.');
            }
            self::$migrated[$name] = true;
        }

        return $app;
    }
}
