<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Runa\Management\Actions\RunSourceChecks;
use Symfony\Component\Console\Output\BufferedOutput;
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

it('selects the cold bootstrap through the single PHP entrypoint before application initialization', function (): void {
    $process = new Process([PHP_BINARY, base_path('artisan'), 'runa:dev', 'status', '--plan'], base_path(), [
        'APP_ENV' => 'production',
        'APP_KEY' => '',
        'DB_CONNECTION' => 'unavailable',
        'APP_CONFIG_CACHE' => '/missing/config.php',
    ]);
    $process->mustRun();

    expect(json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR)['operation'])->toBe('development.status');
    expect(is_file(base_path('management/artisan.php')))->toBeFalse();
});

it('exposes canonical tooling help and cold plans without booting application services', function (): void {
    foreach (['doctor', 'quality', 'knowledge', 'security', 'test', 'production', 'deploy', 'backup', 'install'] as $tool) {
        $process = new Process([PHP_BINARY, base_path('artisan'), 'runa:'.$tool, '--help'], base_path(), [
            'APP_ENV' => 'production', 'APP_KEY' => '', 'DB_CONNECTION' => 'unavailable',
        ]);
        $process->mustRun();
        expect($process->getOutput())->toContain('runa:'.$tool);
    }
    expect(Artisan::call('runa:quality', ['action' => 'final', '--background' => true, '--plan' => true]))->toBe(0);
    $plan = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    expect($plan['operation'])->toBe('tooling.quality')
        ->and($plan['parameters']['arguments'])->toBe(['final', '--background']);
});

it('keeps all development command families out of the production registry', function (): void {
    $process = new Process([PHP_BINARY, base_path('artisan'), 'list', '--raw'], base_path(), ['APP_ENV' => 'production']);
    $process->mustRun();
    foreach (['doctor', 'quality', 'knowledge', 'security', 'test', 'format', 'xdebug', 'dev', 'hook'] as $tool) {
        expect($process->getOutput())->not->toContain('runa:'.$tool.' ');
    }
});

it('preserves literal runner arguments, nonzero exits and test suite exclusion in PHP actions', function (): void {
    $root = sys_get_temp_dir().'/runa-checks-'.bin2hex(random_bytes(6));
    mkdir($root.'/src/vendor/bin', 0775, true);
    $runner = $root.'/src/vendor/bin/pest';
    file_put_contents($runner, '#!/usr/bin/env php'."\n".'<?php fwrite(STDOUT, json_encode(array_slice($argv, 1))); exit(7);');
    chmod($runner, 0755);
    $output = new BufferedOutput;
    try {
        $arguments = ['--filter=literal $(false); value', '--compact'];
        $action = new RunSourceChecks;
        expect($action->execute('test', $arguments, $root, $output))->toBe(7)
            ->and(json_decode($output->fetch(), true, flags: JSON_THROW_ON_ERROR))->toBe($arguments);
        $lock = fopen($root.'/.runtime/tests/suite.lock', 'c');
        flock($lock, LOCK_EX);
        try {
            expect(fn () => $action->execute('test', [], $root, $output))->toThrow(InvalidArgumentException::class, 'Another test suite');
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    } finally {
        File::deleteDirectory($root);
    }
});

it('keeps host bindings after passthrough runner flags', function (): void {
    $request = str_repeat('a', 32);
    $process = new Process([PHP_BINARY, base_path('artisan'), 'runa:test', '--runner-specific-option=literal $(false)', '--plan', '--host-request='.$request], base_path());
    $process->mustRun();
    $plan = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    expect($plan['request_id'])->toBe($request)->and($plan['parameters']['arguments'])->toBe(['--runner-specific-option=literal $(false)']);
});

it('rejects invalid operational scopes before emitting an executable plan', function (array $arguments): void {
    $process = new Process([PHP_BINARY, base_path('artisan'), ...$arguments, '--plan'], base_path());
    expect($process->run())->toBe(2)->and($process->getOutput())->not->toContain('schema_version');
})->with([
    [['runa:dev', 'recreate', 'nginx;false']],
    [['runa:dev', 'embeddings', 'unsafe']],
    [['runa:production', 'logs', 'app;false']],
    [['runa:backup', 'verify', '../../escape']],
    [['runa:project', 'mark-production-deployed']],
    [['runa:knowledge', 'status', '--device=unsafe']],
]);
