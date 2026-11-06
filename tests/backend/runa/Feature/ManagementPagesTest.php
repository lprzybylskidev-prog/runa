<?php

use Illuminate\Support\Facades\DB;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\HealthDeadline;
use Runa\ModuleFramework\HealthPolicies;
use Runa\ModuleFramework\LifecyclePolicies;
use Runa\ModuleFramework\Management\ApplicationManagement;
use Runa\ModuleFramework\Management\ManagementBuilds;
use Runa\ModuleFramework\Management\ManagementPages;
use Runa\ModuleFramework\ModuleAutoloader;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleDiscovery;
use Runa\ModuleFramework\ModuleHealth;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\Transactions\HealthAdmissions;
use Runa\Support\Modules\Api\ModuleApis;
use Tests\Fixtures\ApiCalls;
use Tests\Fixtures\ModuleSources;

beforeEach(function (): void {
    ApiCalls::$constructed = ApiCalls::$executed = 0;
    ApiCalls::$schema = 1;
    ApiCalls::$version = '1.0.0';
    $this->sources = new ModuleSources;
    $this->code = 'adminpages'.bin2hex(random_bytes(6));
    $this->id = 'app.'.$this->code;
    $namespace = 'App\\Modules\\'.ucfirst($this->code);
    $this->descriptor = $namespace.'\\Definitions\\Descriptor';
    $manifest = $this->sources->add('app', $this->code);
    $manifest['declarations']->management = [$this->descriptor];
    $manifest['declarations']->apis = [$namespace.'\\Definitions\\PublicApi'];
    $this->sources->write('app', $this->code, $manifest);
    $root = $this->sources->root.'/app/modules/'.$this->code.'/backend';
    mkdir($root.'/Contracts', 0775, true);
    foreach (['Contracts/Lookup', 'Contracts/Entry', 'LookupHandler', 'LookupProxy', 'Definitions/PublicApi'] as $fixture) {
        file_put_contents($root.'/'.$fixture.'.php', str_replace('MODULE_NAMESPACE', $namespace, file_get_contents(__DIR__.'/../Fixtures/PublicApi/'.$fixture.'.php.stub')));
    }
    file_put_contents($root.'/Definitions/Descriptor.php', str_replace(['MODULE_NAMESPACE', 'MODULE_ID'], [$namespace, $this->id], file_get_contents(__DIR__.'/../Fixtures/Management/AdminModuleProvider.php.stub')));
    file_put_contents($root.'/Definitions/Handler.php', str_replace('App\\Management\\FIXTURE_NAMESPACE', $namespace.'\\Definitions', file_get_contents(__DIR__.'/../Fixtures/Management/AdminHandler.php.stub')));
    file_put_contents($this->sources->root.'/app/modules/'.$this->code.'/backend/Definitions/WorkPolicy.php', '<?php namespace '.$namespace.'\\Definitions; final class WorkPolicy implements \Runa\Contracts\Modules\LifecyclePolicy { public function acceptsDataVersion(?string $installedVersion, string $sourceVersion): bool { return true; } public function schemaVersion(): int { return 1; } public function plan(string $intent): \Runa\Contracts\Modules\LifecyclePlan { return new \Runa\Contracts\Modules\LifecyclePlan("fixture"); } public function prepare(\Runa\Contracts\Modules\TransitionContext $context, ?string $token): \Runa\Contracts\Modules\Preparation { return new \Runa\Contracts\Modules\Preparation("ready"); } public function compensate(\Runa\Contracts\Modules\TransitionContext $context, ?string $token): bool { return true; } }');
    $this->registry = new ModuleRegistry(DB::connection(), new ModuleDiscovery($this->sources->root));
    $loader = new ModuleAutoloader($this->sources->root, $this->registry, true);
    app()->instance(ModuleRegistry::class, $this->registry);
    $declarations = new ModuleDeclarations($this->sources->root, app(), $loader, true);
    app()->instance(ModuleDeclarations::class, $declarations);
    $health = new ModuleHealth(app(HealthAdmissions::class), DB::connection(), $this->registry, new HealthPolicies($this->sources->root, app(), $loader, true), new HealthDeadline);
    $availability = new ModuleAvailability($this->registry, $health, new LifecyclePolicies($this->sources->root, app(), $loader, true), '0.4.0');
    app()->instance(ModuleAvailability::class, $availability);
    app()->forgetInstance(ModuleApis::class);
    $this->registry->synchronize('app');
    $this->pages = new ManagementPages($this->registry, $declarations, $availability, new ApplicationManagement(app(), $this->sources->root), $this->sources->root);
});

afterEach(function (): void {
    DB::table('shared.modules')->where('module_id', $this->id)->delete();
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    $this->sources->remove();
    ApiCalls::$constructed = ApiCalls::$executed = 0;
    ApiCalls::$schema = 1;
    ApiCalls::$version = '1.0.0';
});

it('discovers only available module declarations without constructing business handlers', function (): void {
    expect($this->pages->discover()['owners'])->not->toHaveKey($this->id);
    DB::table('shared.modules')->where('module_id', $this->id)->update(['enabled' => true]);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    $found = $this->pages->discover();
    expect($found['owners'][$this->id]['pages'])->toHaveKey('index')->and($found['diagnostics'])->toBe([])
        ->and(array_keys($found['owners'][$this->id]['title']))->toBe(['pl', 'en'])
        ->and(ApiCalls::$constructed)->toBe(0)->and(ApiCalls::$executed)->toBe(0);
    $class = $this->descriptor;
    foreach (['method' => 'missing', 'target' => 'app.undeclared', 'missingTargets' => true, 'duplicate' => true, 'component' => 'app.foreign.panel'] as $property => $value) {
        $previous = $class::$$property;
        $class::$$property = $value;
        $found = $this->pages->discover();
        expect($found['owners'])->not->toHaveKey($this->id)
            ->and($found['diagnostics'][$this->id])->toBe('admin_declaration_unavailable');
        $class::$$property = $previous;
    }
    expect(ApiCalls::$constructed)->toBe(0)->and(ApiCalls::$executed)->toBe(0);
    DB::table('shared.modules')->where('module_id', $this->id)->update(['manifest_hash' => str_repeat('0', 64)]);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    expect($this->pages->discover()['owners'])->not->toHaveKey($this->id);
});

it('binds module component projection to source manifest and generated build evidence', function (): void {
    DB::table('shared.modules')->where('module_id', $this->id)->update(['enabled' => true]);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    $directory = $this->sources->root.'/app/modules/'.$this->code.'/frontend';
    mkdir($directory.'/components', 0775, true);
    mkdir($this->sources->root.'/public/build', 0775, true);
    file_put_contents($directory.'/components/panel.vue', '<template>Panel</template>');
    $this->pages->discover();
    $class = $this->descriptor;
    $class::$component = $this->id.'.panel';
    $hash = $this->registry->snapshot()->modules[$this->id]->manifestHash;
    $builds = new ManagementBuilds($this->sources->root);
    $evidence = $builds->evidence($directory, $hash);
    $entry = ['manifest_hash' => $hash, ...$evidence, 'components' => [$class::$component]];
    $file = $this->sources->root.'/public/build/runa-components.json';
    file_put_contents($file, json_encode(['schema_version' => 1, 'owners' => [$this->id => $entry]], JSON_THROW_ON_ERROR));
    $view = $this->pages->discover()['owners'][$this->id]['components'][$class::$component];
    expect($view)->toBe(['key' => $class::$component, 'owner' => $this->id, 'manifest_hash' => $hash, 'build_id' => $evidence['build_id']])
        ->and(ApiCalls::$constructed)->toBe(0)->and(ApiCalls::$executed)->toBe(0);
    $entry['manifest_hash'] = str_repeat('0', 64);
    file_put_contents($file, json_encode(['schema_version' => 1, 'owners' => [$this->id => $entry]], JSON_THROW_ON_ERROR));
    expect($this->pages->discover()['owners'][$this->id]['components'][$class::$component]['build_id'])->toBeNull();
    // Production carries compiled evidence, not development-only Vue sources.
    $entry['manifest_hash'] = $hash;
    file_put_contents($file, json_encode(['schema_version' => 1, 'owners' => [$this->id => $entry]], JSON_THROW_ON_ERROR));
    unlink($directory.'/components/panel.vue');
    $compiled = new ManagementBuilds($this->sources->root, true);
    expect($compiled->component($this->id, $class::$component, $directory, $hash))->toBe($view);
    $compiledPages = new ManagementPages($this->registry, app(ModuleDeclarations::class), app(ModuleAvailability::class), new ApplicationManagement(app(), $this->sources->root), $this->sources->root, true);
    expect($compiledPages->discover()['owners'][$this->id]['components'][$class::$component])->toBe($view);
    $entry['build_id'] = str_repeat('0', 64);
    file_put_contents($file, json_encode(['schema_version' => 1, 'owners' => [$this->id => $entry]], JSON_THROW_ON_ERROR));
    expect($compiled->component($this->id, $class::$component, $directory, $hash)['build_id'])->toBeNull();
});
