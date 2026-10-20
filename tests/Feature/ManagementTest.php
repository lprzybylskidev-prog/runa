<?php

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

it('registers the same management command in normal Artisan without executing host actions', function (): void {
    expect(Artisan::call('runa:dev', ['action' => 'status', '--plan' => true]))->toBe(0);
    expect(json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR))->toBe([
        'schema_version' => 1,
        'request_id' => null,
        'operation' => 'development.status',
        'parameters' => [],
    ]);
});

it('rejects unknown actions and invalid request bindings before producing a plan', function (): void {
    expect(Artisan::call('runa:dev', ['action' => 'destroy']))->toBe(2);
    expect(Artisan::output())->not->toContain('schema_version');
    expect(Artisan::call('runa:dev', ['action' => 'up', '--host-request' => 'unsafe']))->toBe(2);
    expect(Artisan::output())->not->toContain('schema_version');
});

it('keeps the management command out of the production application registry', function (): void {
    $process = new Process([PHP_BINARY, base_path('artisan'), 'list', '--raw'], base_path(), ['APP_ENV' => 'production']);
    $process->mustRun();

    expect($process->getOutput())->not->toContain('runa:dev');
});
