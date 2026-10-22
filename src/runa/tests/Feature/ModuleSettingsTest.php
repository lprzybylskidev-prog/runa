<?php

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Encryption\Encrypter as LaravelEncrypter;
use Illuminate\Support\Facades\DB;
use Runa\Contracts\Modules\SettingDefinition;
use Runa\Contracts\Modules\SettingsActor;
use Runa\Contracts\Modules\SettingType;
use Runa\Support\Modules\Api\ModuleDeclarations;
use Runa\Support\Modules\ModuleAutoloader;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\ModuleTransitions;
use Runa\Support\Modules\Settings\ModuleSettings;
use Runa\Support\Modules\Settings\SettingsDefinitions;
use Runa\Support\Modules\Settings\SettingTypes;
use Symfony\Component\Process\Process;
use Tests\Fixtures\ModuleSources;
use Tests\Fixtures\SettingsCalls;

beforeEach(function (): void {
    SettingsCalls::reset();
    $this->sources = new ModuleSources;
    $code = 'settings'.bin2hex(random_bytes(6));
    $this->module = 'app.'.$code;
    $namespace = 'App\\Modules\\'.ucfirst($code);
    $manifest = $this->sources->add('app', $code);
    $manifest['declarations']->settings = [$namespace.'\\Definitions\\Settings'];
    $this->sources->write('app', $code, $manifest);
    file_put_contents($this->sources->root.'/app/modules/'.$code.'/backend/Definitions/Settings.php', str_replace('MODULE_NAMESPACE', $namespace, file_get_contents(__DIR__.'/../Fixtures/Settings/Settings.php.stub')));
    $this->registry = new ModuleRegistry(DB::connection(), new ModuleDiscovery($this->sources->root));
    $loader = new ModuleAutoloader($this->sources->root, $this->registry, true);
    $declarations = new ModuleDeclarations($this->sources->root, app(), $loader, true);
    $this->types = new SettingTypes;
    $this->definitions = new SettingsDefinitions($declarations, $this->types, app(Factory::class));
    $this->settings = new ModuleSettings(DB::connection(), $this->registry, $this->definitions, app(Encrypter::class), new SettingsCalls);
    $this->registry->synchronize('app');
    $this->update = function (array $values, ?SettingsActor $actor = null) {
        $state = $this->settings->read($this->module);

        return $this->settings->update($this->module, $values, $state['revision'], $state['generation'], $actor);
    };
});

afterEach(function (): void {
    DB::table('shared.module_transitions')->where('target', $this->module)->delete();
    DB::table('shared.modules')->where('module_id', $this->module)->delete();
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    $this->sources->remove();
    SettingsCalls::reset();
});

it('validates defaults and exact built in types without coercion and supports registered types', function (): void {
    expect($this->settings->read($this->module)['settings']['attempts']['value'])->toBe(3);
    foreach ([['attempts' => '3'], ['attempts' => 0], ['rate' => 0.1], ['active' => 1], ['mode' => 'missing'], ['tags' => 'x'], ['label' => 'invalid'], ['unknown' => true], ['token' => str_repeat('s', 40000)]] as $patch) {
        expect(fn () => ($this->update)($patch))->toThrow(RuntimeException::class);
    }
    expect(SettingsCalls::$contexts)->toBe([])->and($this->settings->read($this->module)['revision'])->toBe(0);
    SettingsCalls::$invalidDefault = true;
    expect(fn () => $this->settings->read($this->module))->toThrow(RuntimeException::class, 'Invalid settings declaration or defaults.');
    SettingsCalls::$invalidDefault = false;
    $definition = new SettingDefinition('custom', 'positive', 1, ['pl' => 'Test', 'en' => 'Test'], ['pl' => 'Opis', 'en' => 'Description']);
    expect(fn () => $this->types->accepts($definition, 1))->toThrow(RuntimeException::class);
    $this->types->register('positive', new class implements SettingType
    {
        public function accepts(mixed $value, array $options): bool
        {
            return is_int($value) && $value > 0;
        }
    });
    expect($this->types->accepts($definition, 1))->toBeTrue()->and($this->types->accepts($definition, 0))->toBeFalse();
    expect(fn () => $this->types->register('string', new class implements SettingType
    {
        public function accepts(mixed $value, array $options): bool
        {
            return true;
        }
    }))->toThrow(RuntimeException::class);
});

it('encrypts secrets and projects only set state with safe hook changes and nullable actor context', function (): void {
    DB::enableQueryLog();
    try {
        $result = ($this->update)(['label' => 'updated', 'token' => 'test-private-value']);
        expect($result->status)->toBe('succeeded');
        $view = $this->settings->read($this->module);
        expect($view['revision'])->toBe(1)->and($view['settings']['token'])->toHaveKeys(['definition', 'set'])->not->toHaveKey('value')->and($view['settings']['token']['set'])->toBeTrue();
        expect($this->settings->value($this->module, 'token'))->toBe('test-private-value');
        $terminal = DB::table('shared.module_transitions')->where('public_id', $result->operationId)->value('payload');
        expect(app(Encrypter::class)->decrypt($terminal, false))->not->toContain('test-private-value')->not->toContain('candidate');
        expect(json_encode([$view, SettingsCalls::$contexts, SettingsCalls::$changes, DB::getQueryLog()]))->not->toContain('test-private-value');
        expect(SettingsCalls::$contexts[0]->changes['label'])->toBe(['old' => 'initial', 'new' => 'updated']);
        expect(SettingsCalls::$contexts[0]->changes['token'])->toBe(['replaced' => true])->and(SettingsCalls::$contexts[0]->actor)->toBeNull()->and(SettingsCalls::$contexts[0]->application)->toBe('reinitialize');
        expect(DB::table('shared.modules')->where('module_id', $this->module)->value('settings_values'))->not->toContain('test-private-value');
        expect($this->registry->snapshot()->fenced)->toBe([])->and($this->registry->snapshot()->modules[$this->module]->enabled)->toBeFalse();
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }
    ($this->update)(['token' => null], new SettingsActor('system', 'rotation'));
    expect($this->settings->read($this->module)['settings']['token']['set'])->toBeFalse()->and(SettingsCalls::$contexts[1]->actor->id)->toBe('rotation');
});

it('applies immediate settings behind a durable fence outside transactions and rejects concurrent writes', function (): void {
    config(['database.connections.settings_observer' => config('database.connections.pgsql')]);
    $other = DB::connection('settings_observer');
    try {
        $old = $this->settings->read($this->module);
        SettingsCalls::$apply = function ($context) use ($other): bool {
            expect(DB::connection()->transactionLevel())->toBe(0)->and($context->application)->toBe('immediate');
            expect($this->registry->snapshot()->fenced)->toBe([$this->module]);
            expect($this->settings->value($this->module, 'label'))->toBe('new');
            expect($other->selectOne('SELECT pg_try_advisory_lock(1381322305, 4) AS acquired')->acquired)->toBeFalse();
            expect(fn () => $this->registry->synchronize('app'))->toThrow(RuntimeException::class);
            expect(fn () => ($this->update)(['label' => 'nested']))->toThrow(RuntimeException::class);

            return true;
        };
        expect(($this->update)(['label' => 'new'], new SettingsActor('actor', 'user:123'))->status)->toBe('succeeded');
        expect(SettingsCalls::$changes[0]->context->actor->kind)->toBe('actor')->and(SettingsCalls::$changes[0]->outcome)->toBe('succeeded');
        expect(fn () => $this->settings->update($this->module, ['label' => 'stale'], $old['revision'], $old['generation']))->toThrow(RuntimeException::class);
    } finally {
        $other->disconnect();
        DB::purge('settings_observer');
    }
});

it('restores previous working values and compensates failed applications with safe failure hooks', function (): void {
    ($this->update)(['label' => 'working', 'token' => 'test-old-private']);
    SettingsCalls::$apply = fn () => throw new RuntimeException('test-new-private');
    SettingsCalls::$compensate = function ($context): bool {
        expect($this->settings->value($this->module, 'label'))->toBe('working')->and($this->settings->value($this->module, 'token'))->toBe('test-old-private');
        expect($this->registry->snapshot()->fenced)->toBe([$this->module]);

        return true;
    };
    $result = ($this->update)(['label' => 'broken', 'token' => 'test-new-private']);
    expect($result->status)->toBe('failed')->and($this->registry->snapshot()->fenced)->toBe([]);
    expect($this->settings->read($this->module)['revision'])->toBe(3)->and($this->settings->value($this->module, 'label'))->toBe('working');
    expect(json_encode([SettingsCalls::$contexts, SettingsCalls::$changes, $result]))->not->toContain('test-old-private')->not->toContain('test-new-private');
    expect(SettingsCalls::$changes[2]->outcome)->toBe('failed')->and(SettingsCalls::$changes[2]->change)->toBe(['old' => 'working', 'new' => 'broken']);
});

it('retains a recovery fence for ambiguous compensation and recovers idempotently without reapplying', function (): void {
    SettingsCalls::$apply = fn () => false;
    SettingsCalls::$compensate = fn () => false;
    $result = ($this->update)(['label' => 'candidate']);
    expect($result->status)->toBe('recovery_required')->and($this->registry->snapshot()->fenced)->toBe([$this->module]);
    expect($this->settings->value($this->module, 'label'))->toBe('initial');
    expect(fn () => ($this->update)(['label' => 'competing']))->toThrow(RuntimeException::class);
    SettingsCalls::$compensate = fn () => true;
    expect($this->settings->recover($result->operationId)->status)->toBe('failed')->and($this->registry->snapshot()->fenced)->toBe([]);
    $count = count(SettingsCalls::$contexts);
    expect($this->settings->recover($result->operationId)->status)->toBe('failed')->and(SettingsCalls::$contexts)->toHaveCount($count);
    expect($this->settings->read($this->module)['revision'])->toBe(2);
});

it('fails closed on lost encryption key and isolates independent observer exceptions', function (): void {
    ($this->update)(['token' => 'test-private-value']);
    $wrongKey = new ModuleSettings(DB::connection(), $this->registry, $this->definitions, new LaravelEncrypter(random_bytes(32), 'AES-256-CBC'), new SettingsCalls);
    expect(fn () => $wrongKey->read($this->module))->toThrow(RuntimeException::class, 'Settings key or encrypted data unavailable');
    expect(fn () => $wrongKey->update($this->module, ['label' => 'no'], 1, $this->registry->snapshot()->generation))->toThrow(RuntimeException::class);
    expect($this->settings->value($this->module, 'token'))->toBe('test-private-value')->and($this->registry->snapshot()->fenced)->toBe([]);
    SettingsCalls::$observe = fn () => throw new RuntimeException('independent observer failed');
    expect(($this->update)(['label' => 'ok'])->status)->toBe('succeeded')->and($this->settings->value($this->module, 'label'))->toBe('ok');
});

it('recovers real process interruption conservatively and rejects lifecycle recovery for settings operations', function (): void {
    $worker = <<<'WORKER'
require 'runa/tests/bootstrap.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app->make(Runa\Support\TestDatabaseGuard::class)->assertSafe();
$db = Illuminate\Support\Facades\DB::connection();
$registry = new Runa\Support\Modules\ModuleRegistry($db, new Runa\Support\Modules\ModuleDiscovery($argv[1]));
$loader = new Runa\Support\Modules\ModuleAutoloader($argv[1], $registry, true);
$declarations = new Runa\Support\Modules\Api\ModuleDeclarations($argv[1], $app, $loader, true);
$definitions = new Runa\Support\Modules\Settings\SettingsDefinitions($declarations, new Runa\Support\Modules\Settings\SettingTypes, $app->make(Illuminate\Contracts\Validation\Factory::class));
$settings = new Runa\Support\Modules\Settings\ModuleSettings($db, $registry, $definitions, $app->make(Illuminate\Contracts\Encryption\Encrypter::class), new Tests\Fixtures\SettingsCalls);
Tests\Fixtures\SettingsCalls::$apply = static function () { exit(23); };
$view = $settings->read($argv[2]);
$settings->update($argv[2], ['label' => 'interrupted'], $view['revision'], $view['generation']);
WORKER;
    $process = new Process([PHP_BINARY, '-r', $worker, $this->sources->root, $this->module], base_path(), ['APP_ENV' => 'testing', 'DB_DATABASE' => DB::connection()->getDatabaseName(), 'LARAVEL_STORAGE_PATH' => storage_path()]);
    expect($process->run())->toBe(23, $process->getErrorOutput());
    $operation = DB::table('shared.module_transitions')->where('target', $this->module)->first();
    expect($operation->status)->toBe('preparing')->and($this->registry->snapshot()->fenced)->toBe([$this->module]);
    expect($this->settings->value($this->module, 'label'))->toBe('interrupted');
    expect(fn () => app(ModuleTransitions::class)->resume($operation->public_id))->toThrow(RuntimeException::class, 'Unknown module transition.');
    expect($this->settings->recover($operation->public_id)->status)->toBe('failed');
    expect($this->settings->value($this->module, 'label'))->toBe('initial')->and(SettingsCalls::$contexts)->toHaveCount(1)->and($this->registry->snapshot()->fenced)->toBe([]);
});

it('keeps fences when restoring previous database values fails and recovers after the constraint is repaired', function (): void {
    ($this->update)(['label' => 'working']);
    $old = DB::table('shared.modules')->where('module_id', $this->module)->value('settings_values');
    $pdo = DB::connection()->getPdo();
    SettingsCalls::$apply = function () use ($old, $pdo): bool {
        DB::statement('ALTER TABLE shared.modules ADD CONSTRAINT settings_test_restore CHECK (module_id <> '.$pdo->quote($this->module).' OR settings_values IS DISTINCT FROM '.$pdo->quote($old).')');

        return false;
    };
    try {
        $result = ($this->update)(['label' => 'candidate']);
        expect($result->status)->toBe('recovery_required')->and($this->registry->snapshot()->fenced)->toBe([$this->module]);
        expect($this->settings->value($this->module, 'label'))->toBe('candidate');
    } finally {
        DB::statement('ALTER TABLE shared.modules DROP CONSTRAINT IF EXISTS settings_test_restore');
    }
    expect($this->settings->recover($result->operationId)->status)->toBe('failed')->and($this->settings->value($this->module, 'label'))->toBe('working');
});

it('does not overwrite unexpected concurrent state during compensation or release its recovery fence', function (): void {
    SettingsCalls::$apply = function (): bool {
        DB::table('shared.modules')->where('module_id', $this->module)->increment('settings_revision');

        return false;
    };
    $result = ($this->update)(['label' => 'new']);
    expect($result->status)->toBe('recovery_required')->and($this->registry->snapshot()->fenced)->toBe([$this->module]);
    expect($this->settings->value($this->module, 'label'))->toBe('new')->and(SettingsCalls::$contexts)->toHaveCount(1);
});

it('leaves values and flags unchanged when encryption fails before reserving a settings operation', function (): void {
    $encrypter = $this->createMock(Encrypter::class);
    $encrypter->expects($this->once())->method('encrypt')->willThrowException(new RuntimeException('private provider error'));
    $service = new ModuleSettings(DB::connection(), $this->registry, $this->definitions, $encrypter, new SettingsCalls);
    $view = $this->settings->read($this->module);
    expect(fn () => $service->update($this->module, ['token' => 'test-private-value'], $view['revision'], $view['generation']))->toThrow(RuntimeException::class, 'Settings encryption unavailable.');
    expect($this->settings->read($this->module))->toEqual($view)->and($this->registry->snapshot()->fenced)->toBe([]);
    expect(DB::table('shared.module_transitions')->where('target', $this->module)->exists())->toBeFalse();
});
