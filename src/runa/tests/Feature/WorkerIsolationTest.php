<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Runa\Support\TestDatabaseGuard;
use Symfony\Component\Process\Process;

uses(DatabaseTransactions::class);

it('retains one guarded worker suffix after framework transaction setup and in child processes', function (): void {
    app(TestDatabaseGuard::class)->assertSafe();
    $token = getenv('TEST_TOKEN') ?: '0';
    $expected = 'runa_test'.($token === '0' ? '' : '_test_'.$token);
    expect(DB::connection()->getDatabaseName())->toBe($expected);
    $script = <<<'PHP'
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app->make(Runa\Support\TestDatabaseGuard::class)->assertSafe();
echo Illuminate\Support\Facades\DB::connection()->getDatabaseName();
PHP;
    $child = new Process([PHP_BINARY, '-r', $script], base_path(), [
        'APP_ENV' => 'testing', 'DB_DATABASE' => $expected, 'LARAVEL_STORAGE_PATH' => storage_path(), 'TEST_TOKEN' => false,
    ]);
    $child->mustRun();
    expect($child->getOutput())->toBe($expected);
});
