<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Runa\Support\TestDatabaseGuard;

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
