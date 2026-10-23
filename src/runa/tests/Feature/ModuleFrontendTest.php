<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Runa\Contracts\Modules\FrontendPage;
use Runa\Contracts\Modules\SlotContribution;
use Runa\Contracts\Modules\SlotHost;
use Runa\Http\Controllers\ModulePageController;
use Runa\Support\Modules\Api\ModuleDeclarations;
use Runa\Support\Modules\Frontend\ModuleFrontend;
use Runa\Support\Modules\Frontend\ModuleFrontendRoutes;
use Runa\Support\Modules\HealthDeadline;
use Runa\Support\Modules\HealthPolicies;
use Runa\Support\Modules\LifecyclePolicies;
use Runa\Support\Modules\ModuleAutoloader;
use Runa\Support\Modules\ModuleAvailability;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleHealth;
use Runa\Support\Modules\ModuleRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Fixtures\ModuleSources;

beforeEach(function (): void {
    $this->originalSlotOrder = DB::table('shared.module_registry_state')->where('id', 1)->value('slot_order');
    $this->sources = new ModuleSources;
    $this->code = 'frontend'.bin2hex(random_bytes(6));
    $this->id = 'app.'.$this->code;
    $namespace = 'App\\Modules\\'.ucfirst($this->code);
    $this->descriptor = $namespace.'\\Definitions\\Descriptor';
    $manifest = $this->sources->add('app', $this->code);
    $manifest['declarations']->frontend = [$this->descriptor];
    $manifest['declarations']->slots = [$this->descriptor];
    $this->sources->write('app', $this->code, $manifest);
    file_put_contents($this->sources->root.'/app/modules/'.$this->code.'/backend/Definitions/Descriptor.php', str_replace(['MODULE_NAMESPACE', 'MODULE_ID'], [$namespace, $this->id], file_get_contents(__DIR__.'/../Fixtures/Frontend/Descriptor.php.stub')));
    // Source-owned minimal data-compatible lifecycle for healthy runtime projection.
    file_put_contents($this->sources->root.'/app/modules/'.$this->code.'/backend/Definitions/WorkPolicy.php', '<?php namespace '.$namespace.'\\Definitions; final class WorkPolicy implements \Runa\Contracts\Modules\LifecyclePolicy { public function acceptsDataVersion(?string $installedVersion, string $sourceVersion): bool { return true; } public function schemaVersion(): int { return 1; } public function plan(string $intent): \Runa\Contracts\Modules\LifecyclePlan { return new \Runa\Contracts\Modules\LifecyclePlan("fixture"); } public function prepare(\Runa\Contracts\Modules\TransitionContext $context, ?string $token): \Runa\Contracts\Modules\Preparation { return new \Runa\Contracts\Modules\Preparation("ready"); } public function compensate(\Runa\Contracts\Modules\TransitionContext $context, ?string $token): bool { return true; } }');
    $this->registry = new ModuleRegistry(DB::connection(), new ModuleDiscovery($this->sources->root));
    $loader = new ModuleAutoloader($this->sources->root, $this->registry, true);
    app()->instance(ModuleRegistry::class, $this->registry);
    app()->instance(ModuleDeclarations::class, new ModuleDeclarations($this->sources->root, app(), $loader, true));
    $health = new ModuleHealth(DB::connection(), $this->registry, new HealthPolicies($this->sources->root, app(), $loader, true), new HealthDeadline);
    app()->instance(ModuleAvailability::class, new ModuleAvailability($this->registry, $health, new LifecyclePolicies($this->sources->root, app(), $loader, true), '0.4.0'));
    $this->registry->synchronize('app');
    app()->forgetInstance(ModuleFrontend::class);
    $this->frontend = app(ModuleFrontend::class);
});
afterEach(function (): void {
    DB::table('shared.modules')->where('module_id', $this->id)->delete();
    DB::table('shared.module_registry_state')->where('id', 1)->update(['slot_order' => $this->originalSlotOrder]);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    $this->sources->remove();
});
function enableFrontendFixture(object $test): void
{
    DB::table('shared.modules')->where('module_id', $test->id)->update(['enabled' => true]);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
}
it('hides disabled navigation retains direct route metadata and exposes only sanitized public descriptors', function (): void {
    $view = $this->frontend->projection();
    expect($view['schemaVersion'])->toBe(1)->and($view['pages'][0]['availability'])->toBe('hidden')
        ->and($view['pages'][0]['href'])->toBe('/modules/app/'.$this->code.'/index')
        ->and($view['pages'][0]['serverRouteName'])->toBe($this->id.'.pages.index')->and((array) $view['slots'])->toBe([])
        ->and(json_encode($view))->not->toContain('sourcePath')->not->toContain('publicId');
    $this->getJson('/api/modules/frontend')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
});
it('sorts equal priorities by stable identity and applies optimistic persisted ordering', function (): void {
    enableFrontendFixture($this);
    $view = $this->frontend->projection();
    expect($view['pages'][0]['availability'])->toBe('available');
    $slots = (array) $view['slots'];
    expect(array_column($slots[$this->id.'.panel']['contributions'], 'id'))->toBe([$this->id.'.a', $this->id.'.b']);
    expect(fn () => $this->frontend->order($this->id.'.panel', ['named' => $this->id.'.b'], $view['generation']))->toThrow(RuntimeException::class, 'Invalid slot order.');
    $generation = $this->frontend->order($this->id.'.panel', [$this->id.'.b', $this->id.'.missing'], $view['generation']);
    expect(fn () => $this->frontend->order($this->id.'.panel', [], $view['generation']))->toThrow(RuntimeException::class, 'Stale');
    $slots = (array) $this->frontend->projection()['slots'];
    expect(array_column($slots[$this->id.'.panel']['contributions'], 'id'))->toBe([$this->id.'.b', $this->id.'.a']);
    expect($generation)->toBe($this->frontend->projection()['generation']);
});
it('retains inactive ordering across disable missing source and compatible restoration', function (): void {
    enableFrontendFixture($this);
    $this->frontend->order($this->id.'.panel', [$this->id.'.b'], $this->registry->snapshot()->generation);
    DB::table('shared.modules')->where('module_id', $this->id)->update(['enabled' => false]);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    expect((array) $this->frontend->projection()['slots'])->toBe([]);
    $path = $this->sources->root.'/app/modules/'.$this->code;
    rename($path, $this->sources->root.'/retained');
    $this->registry->synchronize('app');
    expect($this->frontend->projection()['pages'])->toBe([]);
    expect(DB::table('shared.module_registry_state')->value('slot_order'))->toContain($this->id.'.b');
    rename($this->sources->root.'/retained', $path);
    $this->registry->synchronize('app');
    enableFrontendFixture($this);
    $slots = (array) $this->frontend->projection()['slots'];
    expect(array_column($slots[$this->id.'.panel']['contributions'], 'id'))->toBe([$this->id.'.b', $this->id.'.a']);
});
it('rejects incompatible props versions and protected contributions without constructing UI', function (): void {
    enableFrontendFixture($this);
    $this->frontend->projection();
    $class = $this->descriptor;
    $class::$version = 2;
    $view = $this->frontend->projection();
    expect((array) $view['diagnostics'])->toHaveKey($this->id.'.a', 'slot_contract_incompatible');
    $class::$version = 1;
    $class::$propType = 'number';
    expect((array) $this->frontend->projection()['diagnostics'])->toHaveKey($this->id.'.a', 'slot_contract_incompatible');
    $class::$propType = 'string';
    $class::$slotPermission = 'slot.protected';
    $slots = (array) $this->frontend->projection()['slots'];
    expect($slots[$this->id.'.panel']['contributions'])->toBe([]);
    $class::$permission = 'view.protected';
    expect($this->frontend->projection()['pages'])->toBe([]);
    expect(fn () => new SlotHost('application.panel', 'panel', 1, ['onClick' => 'string']))->toThrow(InvalidArgumentException::class);
    expect(fn () => new FrontendPage('../escape', $this->id.'.index', ['pl' => 'x', 'en' => 'x']))->toThrow(InvalidArgumentException::class);
    expect(fn () => new SlotContribution('bad', new SlotHost('application.panel', 'panel', 1), 'bad', 1000001))->toThrow(InvalidArgumentException::class);
});
it('keeps enabled unhealthy entries diagnostic and filters slots', function (): void {
    enableFrontendFixture($this);
    DB::table('shared.modules')->where('module_id', $this->id)->update(['installed_version' => '9.0.0']);
    // Invalid policy forces unavailable without changing administrative enablement.
    unlink($this->sources->root.'/app/modules/'.$this->code.'/backend/Definitions/WorkPolicy.php');
    $view = $this->frontend->projection();
    expect($view['pages'][0]['availability'])->toBe('disabled')->and((array) $view['slots'])->toBe([]);
});
it('supports ordinary application hosts and rejects mixed generations', function (): void {
    $this->frontend->applicationHost(new SlotHost('application.panel', 'panel', 1));
    expect((array) $this->frontend->projection()['slots'])->toHaveKey('application.panel');
    $class = $this->descriptor;
    $class::$duringRead = fn () => DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    expect(fn () => $this->frontend->projection())->toThrow(RuntimeException::class, 'snapshot changed');
});

it('revalidates protected deep links through compiled named routes and rejects duplicate registration', function (): void {
    $routes = new ModuleFrontendRoutes(DB::connection(), $this->registry, $this->frontend, app('router'));
    $routes->register();
    expect(fn () => $routes->register())->toThrow(RuntimeException::class, 'Duplicate module page route.');
    app('router')->getRoutes()->refreshNameLookups();
    $compiled = unserialize(serialize(app('router')->getRoutes()->compile()));
    app('router')->setCompiledRoutes($compiled);
    $path = '/modules/app/'.$this->code.'/index';
    $request = Request::create($path);
    $route = app('router')->getRoutes()->match($request);
    expect($route->getName())->toBe($this->id.'.pages.index');
    expect($route->gatherMiddleware())->toBe([]);
    $this->get($path)->assertOk();
    $request->setRouteResolver(fn () => $route);
    $controller = new ModulePageController;
    $response = $controller($request, $this->frontend);
    expect($response->getStatusCode())->toBe(200);
    $class = $this->descriptor;
    $class::$permission = 'view.protected';
    expect(fn () => $controller($request, $this->frontend))->toThrow(NotFoundHttpException::class);
});
