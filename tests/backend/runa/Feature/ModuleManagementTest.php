<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\ManagementAction;
use Runa\Contracts\Modules\ManagementComponent;
use Runa\Contracts\Modules\ManagementPermission;
use Runa\Contracts\Modules\SettingType;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\Events\ModuleIntegrations;
use Runa\ModuleFramework\HealthDeadline;
use Runa\ModuleFramework\HealthPolicies;
use Runa\ModuleFramework\LifecyclePolicies;
use Runa\ModuleFramework\Management\ManagementRenderers;
use Runa\ModuleFramework\Management\ModuleManagement;
use Runa\ModuleFramework\ModuleAutoloader;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleDiscovery;
use Runa\ModuleFramework\ModuleHealth;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\Settings\SettingTypes;
use Runa\Support\Modules\Settings\ModuleSettings;
use Tests\Fixtures\ModuleSources;
use Tests\Fixtures\SettingsCalls;

beforeEach(function (): void {
    SettingsCalls::reset();
    $this->sources = new ModuleSources;
    $this->code = 'management'.bin2hex(random_bytes(6));
    $this->id = 'app.'.$this->code;
    $namespace = 'App\\Modules\\'.ucfirst($this->code);
    $this->descriptor = $namespace.'\\Definitions\\Descriptor';
    $manifest = $this->sources->add('app', $this->code);
    $manifest['declarations']->management = [$this->descriptor];
    $manifest['declarations']->integrations = [$this->descriptor];
    $manifest['dependencies']->optional = (object) ['app.audit_fixture' => '^1.0.0'];
    $manifest['declarations']->settings = [$namespace.'\\Definitions\\Settings'];
    $this->sources->write('app', $this->code, $manifest);
    foreach (['Management/Descriptor', 'Settings/Settings'] as $fixture) {
        file_put_contents($this->sources->root.'/app/modules/'.$this->code.'/backend/Definitions/'.basename($fixture).'.php', str_replace(['MODULE_NAMESPACE', 'MODULE_ID'], [$namespace, $this->id], file_get_contents(__DIR__.'/../Fixtures/'.$fixture.'.php.stub')));
    }
    $this->registry = new ModuleRegistry(DB::connection(), new ModuleDiscovery($this->sources->root));
    $loader = new ModuleAutoloader($this->sources->root, $this->registry, true);
    app()->instance(ModuleRegistry::class, $this->registry);
    app()->instance(ModuleDeclarations::class, new ModuleDeclarations($this->sources->root, app(), $loader, true));
    $health = new ModuleHealth(DB::connection(), $this->registry, new HealthPolicies($this->sources->root, app(), $loader, true), new HealthDeadline);
    app()->instance(ModuleAvailability::class, new ModuleAvailability($this->registry, $health, new LifecyclePolicies($this->sources->root, app(), $loader, true), '0.4.0'));
    $this->registry->synchronize('app');
    app()->forgetInstance(ModuleIntegrations::class);
    $this->management = app(ModuleManagement::class);
});

afterEach(function (): void {
    DB::table('shared.module_work')->where('module_id', DB::table('shared.modules')->where('module_id', $this->id)->value('id'))->delete();
    DB::table('shared.module_transitions')->where('target', $this->id)->delete();
    DB::table('shared.modules')->where('module_id', $this->id)->delete();
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    $this->sources->remove();
    SettingsCalls::reset();
});

it('combines localized versioned metadata and redacted settings while disabled without executing actions', function (): void {
    $settings = app(ModuleSettings::class);
    $state = $settings->read($this->id);
    $settings->update($this->id, ['token' => 'private-management-test'], $state['revision'], $state['generation']);
    SettingsCalls::$contexts = [];
    $view = $this->management->read($this->id);
    expect($view->schemaVersion)->toBe(1)->and($view->status->enabled)->toBeFalse()
        ->and($view->status->available)->toBeFalse()->and($view->metadata)->toHaveKeys(['kind', 'required', 'optional', 'name', 'description'])
        ->and($view->metadata['name']->en)->toBe('Module')->and($view->settings['settings']['token']['set'])->toBeTrue()
        ->and($view->settings['settings']['token'])->not->toHaveKey('value')
        ->and($view->permissions['probe.inspect']->names['en'])->toBe('Inspect')
        ->and($view->actions['inspect']->permission)->toBe('probe.inspect')
        ->and($view->components['inspect']->component)->toBe($this->id.'.inspect')
        ->and($view->activeWork)->toBe([])->and($view->workTruncated)->toBeFalse()
        ->and($view->integrations['audit']['enabled'])->toBeFalse()
        ->and($view->integrations['audit']['definition']->target)->toBe('app.audit_fixture')
        ->and($view->metadata['optional'])->toBe(['app.audit_fixture' => '^1.0.0'])->and($view->diagnostics)->toBe([])
        ->and(SettingsCalls::$contexts)->toBe([])->and(json_encode($view))->not->toContain('private-management-test');
    expect(Artisan::call('runa:modules:management', ['module' => $this->id]))->toBe(0);
    expect(Artisan::output())->toContain('probe.inspect');
});

it('fails closed for unsupported renderers versions duplicate actions foreign keys and undeclared permissions', function (): void {
    $this->management->read($this->id);
    $class = $this->descriptor;
    foreach (['renderer' => 'unknown', 'version' => 2, 'permission' => 'missing', 'component' => 'app.foreign.inspect', 'duplicate' => true] as $field => $value) {
        $previous = $class::$$field;
        $class::$$field = $value;
        $view = $this->management->read($this->id);
        expect($view->diagnostics)->toContain('management_declaration_unavailable')
            ->and($view->actions)->toBe([])->and($view->components)->toBe([])->and($view->permissions)->toBe([])
            ->and($view->settings)->not->toBeNull();
        $class::$$field = $previous;
    }
    $class::$renderer = 'unknown';
    expect(Artisan::call('runa:modules:management', ['module' => $this->id]))->toBe(1);
    expect(Artisan::output())->toContain('management_declaration_unavailable');
    app(ManagementRenderers::class)->register('action', 'unknown', 1);
    expect($this->management->read($this->id)->diagnostics)->toBe([]);
});

it('reports missing source and invalid settings without exposing exception text or changing flags', function (): void {
    SettingsCalls::$invalidDefault = true;
    expect($this->management->read($this->id)->diagnostics)->toBe(['settings_metadata_unavailable']);
    SettingsCalls::$invalidDefault = false;
    File::deleteDirectory($this->sources->root.'/app/modules/'.$this->code);
    $this->registry->synchronize('app');
    $view = $this->management->read($this->id);
    expect($view->status->sourcePresent)->toBeFalse()->and($view->diagnostics)->toBe(['declaration_source_unavailable'])
        ->and($view->settings)->toBeNull()->and($view->actions)->toBe([]);
    expect(Artisan::call('runa:modules:management', ['module' => 'app.unknown']))->toBe(1);
    expect(Artisan::output())->toContain('Module management metadata unavailable.');
});

it('validates schema localization and safe renderer and component identities', function (): void {
    $names = ['pl' => 'Nazwa', 'en' => 'Name'];
    expect(fn () => new ManagementAction('read', 'read', $names, $names, schemaVersion: 2))->toThrow(InvalidArgumentException::class);
    expect(fn () => new ManagementPermission('read', ['en' => 'Read'], $names))->toThrow(InvalidArgumentException::class);
    foreach (['../component.vue', 'https://example.test/a.js', 'import(x)', 'app.test.<script>'] as $component) {
        expect(fn () => new ManagementComponent('panel', $component, 'read'))->toThrow(InvalidArgumentException::class);
    }
    $renderers = new ManagementRenderers;
    expect(fn () => $renderers->register('setting', 'string', 2))->toThrow(RuntimeException::class);
    expect(fn () => $renderers->require('setting', 'custom', 1))->toThrow(RuntimeException::class);
    $renderers->register('setting', 'custom', 2);
    $renderers->require('setting', 'custom', 2);
    expect(fn () => $renderers->require('setting', 'custom', 1))->toThrow(RuntimeException::class);
});

it('bounds active work and excludes private payloads and lease tokens', function (): void {
    $snapshot = $this->registry->snapshot();
    $record = $snapshot->modules[$this->id];
    $internal = DB::table('shared.modules')->where('module_id', $this->id)->value('id');
    $ids = [];
    for ($index = 0; $index < 101; $index++) {
        $ids[] = (string) Str::uuid7();
        DB::table('shared.module_work')->insert([
            'public_id' => $ids[$index], 'module_id' => $internal, 'operation' => 'inspect',
            'manifest_hash' => $record->manifestHash, 'correlation_id' => 'management-test',
            'generation' => $snapshot->generation, 'payload' => 'private-payload-marker',
            'status' => 'pending', 'available_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
    $view = $this->management->read($this->id);
    expect($view->activeWork)->toHaveCount(100)->and($view->workTruncated)->toBeTrue()
        ->and($view->activeWork[0]->workId)->toBe($ids[0])
        ->and($view->activeWork[0]->cancellable)->toBeNull()
        ->and(json_encode($view))->not->toContain('private-payload-marker')->not->toContain('lease_token');
});

it('rejects mixed registry generations instead of publishing a stale view', function (): void {
    $this->management->read($this->id);
    $class = $this->descriptor;
    $class::$duringRead = fn () => DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    try {
        expect(fn () => $this->management->read($this->id))->toThrow(RuntimeException::class, 'Management snapshot changed; retry.');
    } finally {
        $class::$duringRead = null;
    }
});

it('requires a renderer contract separately from a custom backend setting type', function (): void {
    $manifest = json_decode(file_get_contents($this->sources->root.'/app/modules/'.$this->code.'/module.json'), true);
    $namespace = 'App\\Modules\\'.ucfirst($this->code);
    $manifest['declarations']['settings'] = [$namespace.'\\Definitions\\CustomSettings'];
    // Preserve the strict object envelope when rewriting this fixture manifest.
    $manifest = json_decode(json_encode($manifest));
    $manifest->dependencies->required = new stdClass;
    file_put_contents($this->sources->root.'/app/modules/'.$this->code.'/module.json', json_encode($manifest));
    $source = str_replace(['MODULE_NAMESPACE', 'class Settings ', "'label', 'string'"], [$namespace, 'class CustomSettings ', "'label', 'custom'"], file_get_contents(__DIR__.'/../Fixtures/Settings/Settings.php.stub'));
    file_put_contents($this->sources->root.'/app/modules/'.$this->code.'/backend/Definitions/CustomSettings.php', $source);
    $this->registry->synchronize('app');
    app(SettingTypes::class)->register('custom', new class implements SettingType
    {
        public function accepts(mixed $value, array $options): bool
        {
            return is_string($value);
        }
    });
    expect(app(ModuleSettings::class)->read($this->id)['settings']['label']['value'])->toBe('initial');
    expect($this->management->read($this->id)->diagnostics)->toBe(['settings_metadata_unavailable']);
    app(ManagementRenderers::class)->register('setting', 'custom', 1);
    expect($this->management->read($this->id)->diagnostics)->toBe([]);
});

it('projects bounded entity extensions and refuses undeclared entity permissions', function (): void {
    $source = $this->sources->root.'/app/modules/'.$this->code.'/backend/Definitions/Descriptor.php';
    $contents = str_replace('implements ManagementProvider,', 'implements \\Runa\\Contracts\\Modules\\ManagementEntityProvider,', file_get_contents($source));
    $method = <<<'METHOD'
    public static string $entityPermission = 'probe.inspect';

    public function entities(): array
    {
        return [new \Runa\Contracts\Modules\ManagementEntity(
            'entry', self::$entityPermission, ['pl' => 'Wpis', 'en' => 'Entry'], ['pl' => 'Opis', 'en' => 'Description'],
            [new \Runa\Contracts\Modules\ManagementField('name', 'text', ['pl' => 'Nazwa', 'en' => 'Name'], ['pl' => 'Opis', 'en' => 'Description'])],
            [new \Runa\Contracts\Modules\ManagementOperation(new \Runa\Contracts\Modules\ApiOperation('inspect', 'probe.inspect'), stepUp: 'both', previewRequired: true)],
        )];
    }
METHOD;
    $position = strrpos($contents, '}');
    file_put_contents($source, substr($contents, 0, $position).$method."\n}");
    $view = $this->management->read($this->id);
    expect($view->entities['entry']->schemaVersion)->toBe(1);
    expect($view->entities['entry']->operations[0]->previewRequired)->toBeTrue();
    expect($view->diagnostics)->toBe([]);
    $descriptor = $this->descriptor;
    $descriptor::$entityPermission = 'undeclared.read';
    $refused = $this->management->read($this->id);
    expect($refused->entities)->toBe([])->and($refused->diagnostics)->toContain('management_declaration_unavailable');
});
