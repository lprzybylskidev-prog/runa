<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Runa\Management\Actions\RunSourceChecks;
use Runa\Management\Actions\RunTool;
use Runa\Management\DevelopmentContext;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;

it('registers the same management command in normal Artisan without executing host actions', function (): void {
    expect(Artisan::call('runa:dev:status', ['--plan' => true]))->toBe(0);
    expect(json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR))->toBe([
        'schema_version' => 1,
        'request_id' => null,
        'operation' => 'development.status',
        'parameters' => [],
    ]);
});

it('rejects unknown actions and invalid request bindings before producing a plan', function (): void {
    expect(Artisan::call('runa:dev:up', ['--host-request' => 'unsafe']))->toBe(2);
    expect(Artisan::output())->not->toContain('schema_version');
});

it('keeps the management command out of the production application registry', function (): void {
    $process = new Process([PHP_BINARY, base_path('artisan'), 'list', '--raw'], base_path(), ['APP_ENV' => 'production']);
    $process->mustRun();

    expect($process->getOutput())->not->toContain('runa:dev');
});

it('selects the cold bootstrap through the single PHP entrypoint before application initialization', function (): void {
    $process = new Process([PHP_BINARY, base_path('artisan'), 'runa:dev:status', '--plan'], base_path(), [
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
    foreach (['doctor:config', 'quality:plan', 'knowledge:status', 'security:dependencies', 'test', 'production:status', 'deploy'] as $tool) {
        $process = new Process([PHP_BINARY, base_path('artisan'), 'runa:'.$tool, '--help'], base_path(), [
            'APP_ENV' => 'production', 'APP_KEY' => '', 'DB_CONNECTION' => 'unavailable',
        ]);
        $process->mustRun();
        expect($process->getOutput())->toContain('runa:'.$tool);
    }
    expect(Artisan::call('runa:quality:final', ['--background' => true, '--plan' => true]))->toBe(0);
    $plan = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    expect($plan['operation'])->toBe('tooling.quality')
        ->and($plan['parameters']['arguments'])->toBe(['final', '--background']);
});

it('preserves repeated quality groups in programmatic management calls', function () {
    foreach (['plan' => 'show', 'roadmap' => 'roadmap', 'final' => 'final'] as $name => $action) {
        expect(Artisan::call('runa:quality:'.$name, ['--group' => ['docs', 'backend'], '--plan' => true]))->toBe(0);
        $plan = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        expect($plan['parameters']['arguments'])->toBe([$action, '--group=docs', '--group=backend']);
    }
    expect(Artisan::call('runa:quality:final', ['--all' => true, '--group' => ['docs'], '--plan' => true]))->toBe(2);
    expect(Artisan::call('runa:quality:plan', ['--group' => ['installation'], '--plan' => true]))->toBe(2);
    expect(Artisan::call('runa:quality:plan', ['--all' => true, '--plan' => true]))->toBe(0);
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
    mkdir($root.'/src/runa/Testing', 0775, true);
    // This process fixture has no database; lifecycle behavior is covered separately.
    file_put_contents($root.'/src/runa/Testing/worker-databases.php', '<?php exit(0);');
    $runner = $root.'/src/vendor/bin/pest';
    file_put_contents($runner, '#!/usr/bin/env php'."\n".'<?php fwrite(STDOUT, json_encode(array_slice($argv, 1))); exit(7);');
    chmod($runner, 0755);
    $output = new BufferedOutput;
    try {
        $arguments = ['--filter=literal $(false); value', '--compact'];
        $action = new RunSourceChecks;
        expect($action->execute('test', $arguments, $root, $output))->toBe(7)
            ->and(json_decode($output->fetch(), true, flags: JSON_THROW_ON_ERROR))->toBe(['--configuration=phpunit.xml', '--test-directory=../tests/backend/app', ...$arguments]);
        $lock = fopen($root.'/.runtime/tests/suite.lock', 'c');
        flock($lock, LOCK_EX);
        try {
            expect(fn () => $action->execute('test', [], $root, $output))->toThrow(RuntimeException::class, 'Another test suite');
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
    expect($process->run())->not->toBe(0)->and($process->getOutput())->not->toContain('schema_version');
})->with([
    [['runa:dev:recreate', 'nginx;false']],
    [['runa:dev:embeddings:set', 'unsafe']],
    [['runa:production:logs', 'app;false']],
    [['runa:knowledge:status', '--device=unsafe']],
    [['runa:deploy', '../main']],
    [['runa:deploy', 'main;false']],
    [['runa:deploy', 'refs/remotes/origin/main']],
    [['runa:deploy', 'feature//bad']],
    [['runa:deploy', 'bad.lock']],
]);

it('previews customer deployment with a default or explicit customer target', function (array $arguments): void {
    foreach ([[], ['--management']] as $bootstrap) {
        $process = new Process([PHP_BINARY, base_path('artisan'), ...$bootstrap, 'runa:deploy', ...$arguments, '--plan'], base_path());
        $process->mustRun();
        $plan = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        expect($plan['operation'])->toBe('tooling.deploy')->and($plan['parameters']['arguments'])->toBe($arguments);
    }
})->with([[[]], [['main']], [['release/customer-v2']], [['customer-release-42']], [['refs/tags/release-42']], [['--simulation']]]);

it('does not expose retired features through normal or cold Artisan', function (): void {
    foreach (['runa:backup', 'runa:integrity', 'runa:project', 'runa:install', 'runa:dev', 'runa:doctor', 'runa:quality', 'runa:knowledge', 'runa:security', 'runa:xdebug', 'runa:hook', 'runa:production', 'runa:host', 'runa:check'] as $name) {
        expect(Artisan::all())->not->toHaveKey($name);
        $process = new Process([PHP_BINARY, base_path('artisan'), '--management', $name, '--plan']);
        $process->run();
        expect($process->isSuccessful())->toBeFalse();
    }
});

it('keeps owner release diagnostics outside customer Artisan', function (): void {
    foreach (['release', 'cleanliness'] as $area) {
        expect(Artisan::all())->not->toHaveKey('runa:doctor:'.$area);
    }
    expect(Artisan::all())->toHaveKey('runa:hook:pre-commit')->toHaveKey('runa:mail:test');
});

it('binds every named operation to its own fixed action in cold preview', function (): void {
    $definitions = [...json_decode(file_get_contents(base_path('runa/management/operations.json')), true, flags: JSON_THROW_ON_ERROR),
        ...json_decode(file_get_contents(base_path('runa/management/development.json')), true, flags: JSON_THROW_ON_ERROR)];
    $examples = ['runa:deploy' => ['v0.2.1'], 'runa:dev:profile:set' => ['low'],
        'runa:dev:embeddings:set' => ['cpu'], 'runa:dev:recreate' => ['nginx'],
        'runa:knowledge:search' => ['literal $(false); query'], 'runa:hook:commit-msg' => ['message.txt']];
    foreach ($definitions as $definition) {
        $arguments = $examples[$definition['name']] ?? [];
        $process = new Process([PHP_BINARY, base_path('artisan'), $definition['name'], ...$arguments, '--plan']);
        $process->mustRun();
        $plan = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $fixed = [...$definition['prefix'], ...$arguments];
        expect($plan['operation'])->toBe($definition['tool'] === 'dev' ? 'development.'.$fixed[0] : 'tooling.'.$definition['tool']);
        expect($plan['parameters']['arguments'] ?? [])->toBe($definition['tool'] === 'dev' ? array_slice($fixed, 1) : $fixed);
    }
});

it('rejects legacy selectors on retained workflows and fixed commands', function (array $arguments): void {
    $process = new Process([PHP_BINARY, base_path('artisan'), ...$arguments, '--plan']);
    expect($process->run())->not->toBe(0)->and($process->getOutput())->not->toContain('schema_version');
})->with([
    [['runa:test', 'frontend']], [['runa:deploy', 'rollback']],
    [['runa:dev:up', 'down']], [['runa:quality:plan', '--background']],
    [['runa:host:stop', str_repeat('a', 32)]], [['runa:config:validate', '--templates']],
]);

it('rejects local development Actions in production contexts before starting tools', function (): void {
    $root = sys_get_temp_dir().'/runa-dev-context-'.bin2hex(random_bytes(6));
    mkdir($root.'/.git', 0775, true);
    try {
        foreach (['prod', null, 'unknown'] as $mode) {
            file_put_contents($root.'/.git/runa-bootstrap.json', json_encode(['schema_version' => 1, 'mode' => $mode], JSON_THROW_ON_ERROR));
            expect(fn () => (new RunTool)->execute('format', [], $root, new BufferedOutput))
                ->toThrow(InvalidArgumentException::class, 'Development tooling is blocked');
            expect(is_dir($root.'/.runtime'))->toBeFalse();
        }
        file_put_contents($root.'/.git/runa-bootstrap.json', '{invalid');
        expect(fn () => DevelopmentContext::assertAllowed($root))->toThrow(InvalidArgumentException::class);
        unlink($root.'/.git/runa-bootstrap.json');
        foreach (["APP_ENV=production\n", "APP_ENV=\"production\" # target\r\n", "export APP_ENV='production'\n"] as $environment) {
            file_put_contents($root.'/.env', $environment);
            expect(fn () => DevelopmentContext::assertAllowed($root))
                ->toThrow(InvalidArgumentException::class, 'production environment');
        }
        file_put_contents($root.'/.env', "APP_ENV=local\n");
        DevelopmentContext::assertAllowed($root);
        expect(is_dir($root.'/.runtime'))->toBeFalse();
    } finally {
        File::deleteDirectory($root);
    }
});

it('rejects executing development host commands from a production PHP environment', function (): void {
    $process = new Process([PHP_BINARY, base_path('artisan'), 'runa:dev:up'], base_path(), ['APP_ENV' => 'production']);
    expect($process->run())->toBe(2)->and($process->getOutput())->toContain('Development tooling is blocked')
        ->and($process->getOutput())->not->toContain('schema_version');
});
