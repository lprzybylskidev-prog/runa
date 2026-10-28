<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Runa\Contracts\Modules\LifecyclePreview;
use Runa\ModuleFramework\ModuleDiscovery;
use Runa\ModuleFramework\ModuleGraph;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Support\Modules\RegistrySnapshot;
use Tests\Fixtures\ModuleSources;

uses(DatabaseTransactions::class);

beforeEach(function (): void {
    $this->sources = new ModuleSources;
    $this->registry = new ModuleRegistry(DB::connection(), new ModuleDiscovery($this->sources->root));
    $this->add = function (string $id, array $required = [], array $optional = [], string $kind = 'optional', string $version = '1.2.0', string $platform = '^0.4.0', string $api = '^1.0.0'): void {
        [$owner, $code] = explode('.', $id);
        $definition = $this->sources->add($owner, $code, $kind, $version);
        $definition['dependencies'] = (object) ['required' => (object) $required, 'optional' => (object) $optional];
        $definition['platform'] = $platform;
        $definition['framework_api'] = $api;
        $this->sources->write($owner, $code, $definition);
    };
    $this->graph = function (): ModuleGraph {
        $this->registry->synchronize('runa');

        return new ModuleGraph($this->registry->synchronize('app'), '0.4.0');
    };
});

afterEach(function (): void {
    $this->sources->remove();
});

it('orders a shared transitive cascade deterministically without optional activation or writes', function (): void {
    ($this->add)('app.documents', ['app.zeta' => '^1.0.0', 'app.alpha' => '~1.2.0'], ['app.audit' => '^9.0.0']);
    ($this->add)('app.zeta', ['runa.storage' => '>=1.0.0 <2.0.0']);
    ($this->add)('app.alpha', ['runa.storage' => '1.2.0 || ^2.0.0']);
    ($this->add)('runa.storage', kind: 'core');
    $graph = ($this->graph)();
    $before = $this->registry->snapshot();
    DB::connection()->enableQueryLog();
    $preview = $graph->preview('enable', 'app.documents');
    $graph->assertCurrent($preview);
    expect(DB::getQueryLog())->toBe([]);
    DB::connection()->disableQueryLog();
    expect($preview->schemaVersion)->toBe(1)
        ->and($preview->graphReady())->toBeTrue()
        ->and($preview->affected)->toBe(['runa.storage', 'app.alpha', 'app.zeta', 'app.documents'])
        ->and($preview->changes)->toBe(['app.alpha' => true, 'app.zeta' => true, 'app.documents' => true])
        ->and($this->registry->snapshot())->toEqual($before);
});

it('reports transitive missing and incompatible edges with safe complete paths', function (): void {
    ($this->add)('app.documents', ['app.parser' => '^2.0.0']);
    ($this->add)('app.parser', ['runa.storage' => '^1.0.0']);
    $preview = ($this->graph)()->preview('enable', 'app.documents');
    expect($preview->graphReady())->toBeFalse()
        ->and($preview->blockers[0]->code)->toBe('dependency_version')
        ->and($preview->blockers[0]->path)->toBe(['app.documents', 'app.parser'])
        ->and($preview->blockers[0]->constraint)->toBe('^2.0.0')
        ->and($preview->blockers[0]->actualVersion)->toBe('1.2.0')
        ->and($preview->blockers[1]->code)->toBe('not_registered')
        ->and($preview->blockers[1]->path)->toBe(['app.documents', 'app.parser', 'runa.storage']);
});

it('reports full required cycle paths while independent graphs remain usable', function (): void {
    ($this->add)('app.root', ['app.alpha' => '^1.0.0']);
    ($this->add)('app.alpha', ['app.beta' => '^1.0.0']);
    ($this->add)('app.beta', ['app.alpha' => '^1.0.0']);
    ($this->add)('app.independent');
    $graph = ($this->graph)();
    $preview = $graph->preview('enable', 'app.root');
    expect($preview->blockers[0]->code)->toBe('required_cycle')
        ->and($preview->blockers[0]->path)->toBe(['app.root', 'app.alpha', 'app.beta', 'app.alpha'])
        ->and($graph->preview('enable', 'app.independent')->graphReady())->toBeTrue();
    expect(fn () => $graph->assertCurrent($preview))->toThrow(RuntimeException::class, 'blocked');
});

it('keeps optional cycles missing and incompatible targets out of the required graph', function (): void {
    ($this->add)('app.alpha', optional: ['app.beta' => '^2.0.0', 'app.absent' => '^1.0.0']);
    ($this->add)('app.beta', optional: ['app.alpha' => '^1.0.0']);
    $graph = ($this->graph)();
    expect($graph->preview('enable', 'app.alpha')->affected)->toBe(['app.alpha'])
        ->and($graph->preview('enable', 'app.alpha')->graphReady())->toBeTrue()
        ->and($graph->optionalBlockers('app.alpha', 'app.beta')[0]->code)->toBe('dependency_version')
        ->and($graph->optionalBlockers('app.alpha', 'app.absent')[0]->code)->toBe('not_registered')
        ->and($graph->optionalBlockers('app.beta', 'app.alpha'))->toBe([])
        ->and($graph->optionalBlockers('app.beta', 'app.absent')[0]->code)->toBe('optional_not_declared');
});

it('distinguishes required Admin Audit shape from optional Audit consumers', function (): void {
    ($this->add)('runa.admin', ['runa.audit' => '^1.0.0'], kind: 'core');
    ($this->add)('app.documents', optional: ['runa.audit' => '^1.0.0']);
    $graph = ($this->graph)();
    expect($graph->preview('enable', 'runa.admin')->graphReady())->toBeFalse()
        ->and($graph->preview('enable', 'app.documents')->graphReady())->toBeTrue();
});

it('checks source platform and framework compatibility without conflating installed versions', function (): void {
    ($this->add)('app.documents', platform: '^0.5.0', api: '^2.0.0');
    $graph = ($this->graph)();
    $preview = $graph->preview('enable', 'app.documents');
    expect(array_column($preview->blockers, 'code'))->toBe(['platform_version', 'framework_api_version']);
    rename($this->sources->root.'/app/modules/documents', $this->sources->root.'/retained');
    $preview = ($this->graph)()->preview('enable', 'app.documents');
    expect(array_column($preview->blockers, 'code'))->toBe(['source_missing', 'platform_version', 'framework_api_version']);
});

it('disables enabled dependents first including missing source and excludes optional or disabled consumers', function (): void {
    ($this->add)('app.storage');
    ($this->add)('app.parser', ['app.storage' => '^1.0.0']);
    ($this->add)('app.documents', ['app.parser' => '^1.0.0']);
    ($this->add)('app.disabled', ['app.storage' => '^1.0.0']);
    ($this->add)('app.optional_consumer', optional: ['app.storage' => '^1.0.0']);
    ($this->graph)();
    DB::table('shared.modules')->whereIn('module_id', ['app.storage', 'app.parser', 'app.documents', 'app.optional_consumer'])->update(['enabled' => true]);
    rename($this->sources->root.'/app/modules/parser', $this->sources->root.'/retained');
    $graph = ($this->graph)();
    $preview = $graph->preview('disable', 'app.storage');
    expect($preview->graphReady())->toBeTrue()
        ->and($preview->affected)->toBe(['app.documents', 'app.parser', 'app.storage'])
        ->and($preview->changes)->toBe(['app.documents' => false, 'app.parser' => false, 'app.storage' => false])
        ->and($graph->preview('enable', 'app.storage')->affected)->toBe(['app.storage']);
});

it('rejects direct and cascading Core disable without mutating any flags', function (): void {
    ($this->add)('runa.storage');
    ($this->add)('runa.auth', ['runa.storage' => '^1.0.0'], kind: 'core');
    $graph = ($this->graph)();
    $before = $this->registry->snapshot();
    expect($graph->preview('disable', 'runa.auth')->blockers[0]->code)->toBe('core_disable')
        ->and($graph->preview('disable', 'runa.storage')->blockers[0]->path)->toBe(['runa.storage', 'runa.auth'])
        ->and($this->registry->snapshot())->toEqual($before);
});

it('rejects stale generations and altered cascade DTOs even when callers retain an old snapshot', function (): void {
    ($this->add)('app.documents');
    $graph = ($this->graph)();
    $preview = $graph->preview('enable', 'app.documents');
    $graph->assertCurrent($preview);
    ($this->add)('app.parser');
    $fresh = ($this->graph)();
    expect(fn () => $fresh->assertCurrent($preview))->toThrow(RuntimeException::class, 'Stale');
    $current = $fresh->preview('enable', 'app.documents');
    $forged = new LifecyclePreview('enable', 'app.documents', $current->generation, [], [], []);
    expect(fn () => $fresh->assertCurrent($forged))->toThrow(RuntimeException::class, 'altered');
    // Even an invalid caller that changes flags without generation cannot reuse a different plan.
    DB::table('shared.modules')->where('module_id', 'app.documents')->update(['enabled' => true]);
    $fresh = new ModuleGraph($this->registry->snapshot(), '0.4.0');
    expect(fn () => $fresh->assertCurrent($current))->toThrow(RuntimeException::class, 'altered');
});

it('uses retained unregistered metadata only as a blocker and accepts an empty registry', function (): void {
    ($this->add)('app.documents');
    ($this->graph)();
    DB::table('shared.modules')->where('module_id', 'app.documents')->update(['registered' => false]);
    $graph = new ModuleGraph($this->registry->snapshot(), '0.4.0');
    expect($graph->preview('enable', 'app.documents')->blockers[0]->code)->toBe('not_registered')
        ->and($graph->optionalBlockers('app.documents', 'app.absent')[0]->code)->toBe('not_registered');
    $empty = new ModuleGraph(new RegistrySnapshot(1, []), '0.4.0');
    expect($empty->preview('enable', 'runa.auth')->affected)->toBe([])
        ->and($empty->preview('enable', 'runa.auth')->blockers[0]->code)->toBe('not_registered');
    expect(fn () => $empty->preview('uninstall', 'runa.auth'))->toThrow(InvalidArgumentException::class);
});

it('binds dependency metadata to its snapshot and invalidates previews after a source revision', function (): void {
    ($this->add)('app.documents');
    $old = ($this->graph)();
    $preview = $old->preview('enable', 'app.documents');
    $path = $this->sources->root.'/app/modules/documents/module.json';
    $definition = json_decode(file_get_contents($path));
    $definition->dependencies->required = (object) ['app.parser' => '^1.0.0'];
    file_put_contents($path, json_encode($definition, JSON_THROW_ON_ERROR));
    $fresh = ($this->graph)();
    expect($old->preview('enable', 'app.documents'))->toEqual($preview)
        ->and($fresh->preview('enable', 'app.documents')->blockers[0]->path)->toBe(['app.documents', 'app.parser']);
    expect(fn () => $fresh->assertCurrent($preview))->toThrow(RuntimeException::class, 'Stale');
});

it('rejects ownership violations during publication before any graph can consume them', function (string $edge): void {
    ($this->add)('runa.storage', ...[$edge => ['app.documents' => '^1.0.0']]);
    expect(fn () => ($this->graph)())->toThrow(InvalidArgumentException::class, 'direction');
    expect($this->registry->snapshot()->modules)->not->toHaveKey('runa.storage');
})->with(['required', 'optional']);
