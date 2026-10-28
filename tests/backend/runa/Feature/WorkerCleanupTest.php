<?php

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

it('refuses worker cleanup while a test database is in use', function (): void {
    // getDatabaseName alone is configuration and need not keep a live PDO open.
    $name = DB::selectOne('SELECT current_database() AS name')->name;
    if ($name === 'runa_test') {
        $this->markTestSkipped('This regression requires a parallel worker.');
    }
    $cleanup = new Process([PHP_BINARY, 'runa/testing/backend/worker-databases.php'], base_path(), [
        'APP_ENV' => 'testing', 'DB_DATABASE' => 'runa_test', 'TEST_TOKEN' => false,
    ]);
    expect($cleanup->run())->not->toBe(0);
    expect($cleanup->getErrorOutput())->toContain('Active test worker database preserved:');
    expect(DB::selectOne('SELECT current_database() AS name')->name)->toBe($name);
});

it('refuses worker cleanup outside the testing environment before booting the application', function (): void {
    $cleanup = new Process([PHP_BINARY, 'runa/testing/backend/worker-databases.php'], base_path(), ['APP_ENV' => 'production']);
    expect($cleanup->run())->not->toBe(0);
    expect($cleanup->getErrorOutput())->toContain('Worker cleanup requires the isolated testing environment.');
});
