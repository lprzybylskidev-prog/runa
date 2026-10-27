<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Runa\Support\TestDatabaseGuard;
use Symfony\Component\Process\Process;

it('uses an isolated PostgreSQL database with the technical schema', function () {
    app(TestDatabaseGuard::class)->assertSafe();
    $identity = DB::selectOne('SELECT current_database() AS name, session_user AS role');
    expect($identity->role)->toBe('runa_test');
    expect($identity->name)->toMatch('/^runa_test(?:_test_[1-9][0-9]*)?$/');
    expect(DB::selectOne("SELECT count(*) AS total FROM pg_tables WHERE schemaname = 'public'")->total)->toBe(0);
    expect(DB::selectOne("SELECT count(*) AS total FROM pg_tables WHERE schemaname = 'shared'")->total)->toBeGreaterThanOrEqual(3);
    expect(storage_path())->toContain('/.runtime/tests/worker-');
});

it('rejects unsafe configuration before a destructive command runs', function (string $key, mixed $value) {
    config()->set($key, $value);
    expect(fn () => app(TestDatabaseGuard::class)->assertSafe())->toThrow(RuntimeException::class);
    expect(fn () => Artisan::call('migrate:fresh', ['--force' => true]))->toThrow(RuntimeException::class);
})->with([
    ['app.env', 'local'],
    ['database.connections.pgsql.database', 'runa'],
    ['database.connections.pgsql.host', 'other-host'],
    ['database.connections.pgsql.username', 'runa'],
    ['database.default', 'other'],
]);

it('verifies actual connection identity even when the configured name looks safe', function () {
    DB::connection()->getPdo();
    config()->set('database.connections.pgsql.database', 'runa_test_99999');
    expect(fn () => app(TestDatabaseGuard::class)->assertSafe())
        ->toThrow(RuntimeException::class, 'Actual test database identity');
});

it('runs native destructive guard acceptance only inside its disposable worker', function (): void {
    $identity = DB::selectOne('SELECT current_database() AS name, session_user AS role');
    if ($identity->role !== 'runa_test' || preg_match('/\Aruna_test_test_[1-9][0-9]{0,4}\z/D', $identity->name) !== 1) {
        throw new RuntimeException('Native destructive acceptance requires test-backend --parallel.');
    }
    $invoke = function (array $arguments, array $environment = [], ?string $error = null) use ($identity): void {
        $process = new Process([PHP_BINARY, base_path('artisan'), ...$arguments], base_path(), [
            'APP_ENV' => 'testing', 'DB_DATABASE' => $identity->name, 'TEST_TOKEN' => false,
            'LARAVEL_STORAGE_PATH' => storage_path(), ...$environment,
        ]);
        $process->setTimeout(null);
        $process->run();
        if ($error === null) {
            expect($process->isSuccessful())->toBeTrue();
        } else {
            expect($process->isSuccessful())->toBeFalse()
                ->and($process->getOutput().$process->getErrorOutput())->toContain($error);
        }
    };
    foreach (['db:wipe', 'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback'] as $command) {
        $invoke([$command, '--force'], ['APP_ENV' => 'local'], 'Destructive database operations require');
    }
    foreach ([['DB_DATABASE' => 'runa'], ['DB_HOST' => 'localhost'], ['DB_USERNAME' => 'runa'], ['DB_DATABASE' => 'runa_test_prod']] as $environment) {
        $invoke(['migrate:fresh', '--force'], $environment, 'Destructive database operations require');
    }
    $invoke(['db:wipe', '--database=other', '--force'], error: 'guarded pgsql connection');
    $invoke(['runa:test-db:guard']);
    $invoke(['migrate:fresh', '--force']);
});
