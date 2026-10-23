<?php

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Monolog\Handler\TestHandler;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\HttpAccess;
use Runa\Contracts\Modules\HttpAccountPolicy;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\OperationContext;
use Runa\Contracts\Modules\Preparation;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Support\MaintenanceState;
use Runa\Support\Modules\Api\DenyProtectedOperations;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\ModuleDeclarations;
use Runa\Support\Modules\Api\ModuleHttp;
use Runa\Support\Modules\Api\OperationGuard;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Api\OperationScope;
use Runa\Support\Modules\Api\PrincipalScope;
use Runa\Support\Modules\Data\PublicReferenceRule;
use Runa\Support\Modules\HealthDeadline;
use Runa\Support\Modules\HealthPolicies;
use Runa\Support\Modules\LifecyclePolicies;
use Runa\Support\Modules\ModuleAutoloader;
use Runa\Support\Modules\ModuleAvailability;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleHealth;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\ModuleTransitions;
use Tests\Fixtures\ApiCalls;
use Tests\Fixtures\HealthPolicy;
use Tests\Fixtures\ModuleSources;
use Tests\Fixtures\TransitionPolicy;

beforeEach(function (): void {
    $this->sources = new ModuleSources;
    $this->code = 'api'.bin2hex(random_bytes(6));
    $this->id = 'app.'.$this->code;
    $this->namespace = 'App\\Modules\\'.ucfirst($this->code);
    $this->contract = $this->namespace.'\\Contracts\\Lookup';
    $this->directory = $this->sources->root.'/app/modules/'.$this->code;
    $definition = $this->sources->add('app', $this->code);
    $definition['declarations']->apis = [$this->namespace.'\\Definitions\\PublicApi'];
    $definition['declarations']->http = [$this->namespace.'\\Definitions\\Routes'];
    $definition['declarations']->health = [$this->namespace.'\\Definitions\\Checks'];
    $this->sources->write('app', $this->code, $definition);
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../Fixtures/PublicApi', FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        $relative = substr($file->getPathname(), strlen(__DIR__.'/../Fixtures/PublicApi/'));
        $target = $this->directory.'/backend/'.substr($relative, 0, -5);
        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0775, true);
        }
        file_put_contents($target, str_replace('MODULE_NAMESPACE', $this->namespace, file_get_contents($file->getPathname())));
    }
    file_put_contents($this->directory.'/backend/Definitions/WorkPolicy.php', '<?php namespace '.$this->namespace.'\\Definitions; final class WorkPolicy extends \\Tests\\Fixtures\\TransitionPolicy { public const ID = '.var_export($this->id, true).'; }');
    file_put_contents($this->directory.'/backend/Definitions/Checks.php', '<?php namespace '.$this->namespace.'\\Definitions; final class Checks extends \\Tests\\Fixtures\\HealthPolicy { public const ID = '.var_export($this->id, true).'; }');
    $this->registry = new ModuleRegistry(DB::connection(), new ModuleDiscovery($this->sources->root));
    $loader = new ModuleAutoloader($this->sources->root, $this->registry, true);
    app()->instance(ModuleAutoloader::class, $loader);
    $this->lifecycle = new LifecyclePolicies($this->sources->root, app(), $loader, true);
    $this->health = new ModuleHealth(DB::connection(), $this->registry, new HealthPolicies($this->sources->root, app(), $loader, true), new HealthDeadline);
    $this->availability = new ModuleAvailability($this->registry, $this->health, $this->lifecycle, '0.4.0');
    $this->maintenance = new MaintenanceState(app(), DB::connection(), $this->registry, $this->availability);
    $this->declarations = new ModuleDeclarations($this->sources->root, app(), $loader, true);
    $this->scope = new OperationScope;
    app()->instance(OperationScope::class, $this->scope);
    $this->guard = new OperationGuard($this->registry, $this->availability, $this->maintenance, new DenyProtectedOperations, app(), $this->scope);
    $this->apis = new ModuleApis($this->registry, $this->declarations, $this->guard, $this->scope);
    $this->http = new ModuleHttp($this->registry, $this->declarations, app('router'), DB::connection());
    app()->instance(ModuleDeclarations::class, $this->declarations);
    app()->instance(ModuleRegistry::class, $this->registry);
    app()->instance(OperationGuard::class, $this->guard);
    app()->instance(ModuleHttp::class, $this->http);
    app()->instance(MaintenanceState::class, $this->maintenance);
    HealthPolicy::$definitions = [$this->id => ['mode' => 'on_use', 'severity' => 'critical']];
    HealthPolicy::$checks = HealthPolicy::$calls = [];
    ApiCalls::$constructed = ApiCalls::$executed = 0;
    ApiCalls::$schema = 1;
    ApiCalls::$version = '1.0.0';
    $this->registry->synchronize('app');
    $this->setState = function (array $attributes): void {
        DB::table('shared.modules')->where('module_id', $this->id)->update($attributes);
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    };
    ($this->setState)(['enabled' => true]);
    $this->publicId = (string) Str::uuid7();
    $this->url = '/api/app/'.$this->code.'/entries/'.$this->publicId;
});

afterEach(function (): void {
    DB::table('shared.modules')->where('module_id', $this->id)->delete();
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    $this->sources->remove();
});

it('serves an ordinary API-only consumer through typed readonly contracts with per-call checks', function (): void {
    $api = $this->apis->resolve($this->id, $this->contract, '1.0.0');
    expect($api)->toBeInstanceOf($this->contract)->and(ApiCalls::$constructed)->toBe(0);
    expect($api->exists($this->publicId))->toBeTrue();
    expect($api->exists($this->publicId))->toBeTrue();
    expect($api->describe($this->publicId)->publicId)->toBe($this->publicId);
    expect(HealthPolicy::$calls[$this->id])->toBe(3)->and(ApiCalls::$executed)->toBe(2);
});

it('rejects retained references after disable missing source unregister or incompatible installed data', function (array $state): void {
    $api = $this->apis->resolve($this->id, $this->contract, '1.0.0');
    expect($api->exists($this->publicId))->toBeTrue();
    ($this->setState)($state);
    expect(fn () => $api->exists($this->publicId))->toThrow(OperationRejected::class);
    expect(ApiCalls::$executed)->toBe(1)->and(ApiCalls::$constructed)->toBe(1);
})->with([[['enabled' => false]], [['source_present' => false]], [['registered' => false, 'enabled' => false]], [['installed_version' => '9.0.0']]]);

it('rejects unhealthy cached API references then recovers without re-resolution or flag changes', function (): void {
    $api = $this->apis->resolve($this->id, $this->contract, '1.0.0');
    expect($api->exists($this->publicId))->toBeTrue();
    HealthPolicy::$checks[$this->id] = fn () => false;
    expect(fn () => $api->exists($this->publicId))->toThrow(OperationRejected::class);
    expect(ApiCalls::$executed)->toBe(1);
    HealthPolicy::$checks[$this->id] = fn () => true;
    expect($api->exists($this->publicId))->toBeTrue()->and($this->registry->snapshot()->modules[$this->id]->enabled)->toBeTrue();
});

it('rejects unsupported contract versions schemas undeclared calls mutable nested output and source drift', function (): void {
    expect(fn () => $this->apis->resolve($this->id, $this->contract, '2.0.0'))->toThrow(OperationRejected::class);
    ApiCalls::$schema = 2;
    expect(fn () => $this->apis->resolve($this->id, $this->contract, '1.0.0'))->toThrow(OperationRejected::class);
    ApiCalls::$schema = 1;
    $api = $this->apis->resolve($this->id, $this->contract, '1.0.0');
    expect(fn () => $this->apis->invoke($this->id, $this->contract, '1.0.0', '__construct', []))->toThrow(OperationRejected::class);
    expect(fn () => $api->invalid())->toThrow(RuntimeException::class);
    file_put_contents($this->directory.'/module.json', file_get_contents($this->directory.'/module.json')."\n");
    expect(fn () => $api->exists($this->publicId))->toThrow(OperationRejected::class);
});

it('registers namespaced routes with resources and keeps framework validation status', function (): void {
    $this->http->register();
    app('router')->getRoutes()->refreshNameLookups();
    expect(route($this->id.'.read', ['public_id' => $this->publicId], false))->toBe($this->url);
    $this->getJson($this->url)->assertOk()->assertJsonPath('public_id', $this->publicId);
    expect(ApiCalls::$executed)->toBe(1);
    $this->getJson($this->url.'?filter=rejected')->assertUnprocessable();
    expect(ApiCalls::$executed)->toBe(1);
});

it('blocks direct HTTP before constructing a module controller or validating a request', function (bool $healthy): void {
    $this->http->register();
    if (! $healthy) {
        HealthPolicy::$checks[$this->id] = fn () => false;
    } else {
        ($this->setState)(['enabled' => false]);
    }
    $this->getJson($this->url.'?filter=rejected')->assertStatus(503)->assertExactJson(['error' => ['code' => 'module_unavailable', 'module' => $this->id, 'operation' => 'read']])->assertHeader('Cache-Control', 'no-store, private');
    expect(ApiCalls::$constructed)->toBe(0)->and(ApiCalls::$executed)->toBe(0);
})->with([true, false]);

it('denies protected HTTP until a trusted authorizer is supplied and shares stable operation identities', function (): void {
    $this->http->register();
    $this->postJson($this->url)->assertForbidden()->assertJsonPath('error.code', 'module_forbidden');
    expect(ApiCalls::$constructed)->toBe(0);
    $authorizer = new class implements OperationAuthorizer
    {
        public ?OperationContext $observed = null;

        public function allows(OperationContext $context, string $permission): bool
        {
            $this->observed = $context;

            return $permission === 'entries.write';
        }
    };
    app()->instance(OperationGuard::class, new OperationGuard($this->registry, $this->availability, $this->maintenance, $authorizer, app(), $this->scope));
    $this->postJson($this->url)->assertOk();
    expect($authorizer->observed->operation)->toBe('write')->and($authorizer->observed->moduleId)->toBe($this->id)->and(Str::isUuid($authorizer->observed->operationId))->toBeTrue();
});

it('hides source errors in direct HTTP responses', function (): void {
    $this->http->register();
    file_put_contents($this->directory.'/module.json', file_get_contents($this->directory.'/module.json')."\n");
    $this->getJson($this->url)->assertStatus(503)->assertJsonPath('error.code', 'http_contract_unavailable');
    expect(ApiCalls::$constructed)->toBe(0);
});

it('enforces disabled state through serialized compiled routes and rejects duplicate registration atomically', function (): void {
    $this->http->register();
    $routes = app('router')->getRoutes();
    $count = count($routes->getRoutes());
    expect(fn () => $this->http->register())->toThrow(RuntimeException::class);
    expect(count($routes->getRoutes()))->toBe($count);
    $compiled = unserialize(serialize($routes->compile()));
    app('router')->setCompiledRoutes($compiled);
    $this->getJson($this->url)->assertOk();
    ApiCalls::$constructed = ApiCalls::$executed = 0;
    ($this->setState)(['enabled' => false]);
    $this->getJson($this->url)->assertStatus(503)->assertJsonPath('error.code', 'module_unavailable');
    expect(ApiCalls::$constructed)->toBe(0)->and(ApiCalls::$executed)->toBe(0);
});

it('blocks both API and HTTP behind a real pending disable fence without changing enabled flags', function (): void {
    $this->http->register();
    $api = $this->apis->resolve($this->id, $this->contract, '1.0.0');
    TransitionPolicy::$prepare[$this->id] = fn () => new Preparation('waiting', 'private-token');
    $transitions = new ModuleTransitions(DB::connection(), $this->registry, $this->lifecycle, app(Encrypter::class), '0.4.0', $this->health);
    try {
        $pending = $transitions->apply($transitions->preview('disable', $this->id));
        expect($pending->status)->toBe('pending')->and($this->registry->snapshot()->modules[$this->id]->enabled)->toBeTrue();
        expect(fn () => $api->exists($this->publicId))->toThrow(OperationRejected::class);
        $this->getJson($this->url)->assertStatus(503);
        expect(ApiCalls::$constructed)->toBe(0)->and(ApiCalls::$executed)->toBe(0);
        $transitions->cancel($pending->operationId);
        expect($api->exists($this->publicId))->toBeTrue();
    } finally {
        unset(TransitionPolicy::$prepare[$this->id]);
        DB::table('shared.module_transitions')->where('target', $this->id)->delete();
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    }
});

it('blocks stale scheduled observations and retains safe PHP root causes without disclosing HTTP diagnostics', function (): void {
    HealthPolicy::$definitions[$this->id]['mode'] = 'scheduled';
    $this->http->register();
    $api = $this->apis->resolve($this->id, $this->contract, '1.0.0');
    $this->health->recover();
    expect($api->exists($this->publicId))->toBeTrue();
    $this->travel(21)->seconds();
    try {
        $api->exists($this->publicId);
        $this->fail('Expired critical observation admitted an operation.');
    } catch (OperationRejected $error) {
        expect($error->reasons)->not->toBeEmpty()->and($error->reason)->toBe('module_unavailable')->and($error->retryAfter)->toBeNull();
    }
    $this->getJson($this->url)->assertStatus(503)->assertExactJson(['error' => ['code' => 'module_unavailable', 'module' => $this->id, 'operation' => 'read']]);
    $this->travelBack();
});

it('uses declared operation applicability and refuses changed authorization generations before executing', function (): void {
    HealthPolicy::$definitions[$this->id]['operations'] = ['write'];
    HealthPolicy::$checks[$this->id] = fn () => false;
    $this->http->register();
    $api = $this->apis->resolve($this->id, $this->contract, '1.0.0');
    expect($api->exists($this->publicId))->toBeTrue()->and(HealthPolicy::$calls[$this->id] ?? 0)->toBe(0);
    $this->postJson($this->url)->assertStatus(503);
    HealthPolicy::$checks[$this->id] = fn () => true;
    $id = $this->id;
    $authorizer = new class($id) implements OperationAuthorizer
    {
        public function __construct(private string $id) {}

        public function allows(OperationContext $context, string $permission): bool
        {
            DB::table('shared.modules')->where('module_id', $this->id)->update(['enabled' => false]);
            DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');

            return true;
        }
    };
    app()->instance(OperationGuard::class, new OperationGuard($this->registry, $this->availability, $this->maintenance, $authorizer, app(), $this->scope));
    ApiCalls::$constructed = 0;
    $this->postJson($this->url)->assertStatus(503)->assertJsonPath('error.code', 'availability_changed');
    expect(ApiCalls::$constructed)->toBe(0);
});

it('unwinds nested operation correlation even when a handler throws', function (): void {
    $parent = new OperationContext('app.origin', 'forward', (string) Str::uuid7(), 'correlation_42', $this->registry->snapshot()->generation);
    $context = $this->scope->run($parent, fn () => $this->guard->enter($this->id, new ApiOperation('read')));
    expect($context->originModule)->toBe('app.origin')->and($context->correlationId)->toBe('correlation_42')->and($this->scope->current())->toBeNull();
    expect(fn () => $this->scope->run($parent, fn () => throw new RuntimeException('fixture')))->toThrow(RuntimeException::class);
    expect($this->scope->current())->toBeNull();
});

it('keeps source drift boot and synchronization recoverable through a rejecting module fallback', function (): void {
    file_put_contents($this->directory.'/module.json', file_get_contents($this->directory.'/module.json')."\n");
    $this->http->register();
    $this->getJson($this->url)->assertStatus(503)->assertJsonPath('error.code', 'http_contract_unavailable');
    expect(ApiCalls::$constructed)->toBe(0);
    expect($this->registry->synchronize('app')->modules[$this->id]->sourcePresent)->toBeTrue();
    // Rebuilding the route collection on the next uncached boot restores the normal routes.
    expect($this->apis->resolve($this->id, $this->contract, '1.0.0')->exists($this->publicId))->toBeTrue();
});

it('preserves update maintenance when API calls are rejected and resumes after the owner removes its marker', function (): void {
    $directory = storage_path('framework/maintenance');
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
    $api = $this->apis->resolve($this->id, $this->contract, '1.0.0');
    $marker = $directory.'/enabled';
    expect(file_exists($marker))->toBeFalse();
    file_put_contents($marker, 'point7-fixture');
    try {
        expect(fn () => $api->exists($this->publicId))->toThrow(OperationRejected::class);
        expect(file_get_contents($marker))->toBe('point7-fixture')->and(ApiCalls::$constructed)->toBe(0);
    } finally {
        unlink($marker);
    }
    expect($api->exists($this->publicId))->toBeTrue();
});

it('adapts guarded existence validation without converting module unavailability into a missing reference', function (): void {
    $api = $this->apis->resolve($this->id, $this->contract, '1.0.0');
    $rule = new PublicReferenceRule($api);
    $failures = [];
    $rule->validate('public_id', $this->publicId, function (string $message) use (&$failures): void {
        $failures[] = $message;
    });
    expect($failures)->toBe([]);
    ($this->setState)(['enabled' => false]);
    expect(fn () => $rule->validate('public_id', $this->publicId, fn () => null))->toThrow(OperationRejected::class);
});

it('rejects hidden controller middleware and implicit model injection through safe source fallbacks', function (string $mode): void {
    $source = file_get_contents($this->directory.'/backend/Http/ReadEntry.php');
    if ($mode === 'middleware') {
        $source = str_replace('public function __construct()', 'public function getMiddleware(): array { return []; } public function __construct()', $source);
    } else {
        $source = str_replace('ReadEntryRequest $request', '\\Illuminate\\Database\\Eloquent\\Model $request', $source);
    }
    file_put_contents($this->directory.'/backend/Http/ReadEntry.php', $source);
    $this->http->register();
    $this->getJson($this->url)->assertStatus(503)->assertJsonPath('error.code', 'http_contract_unavailable');
    expect(ApiCalls::$constructed)->toBe(0);
})->with(['middleware', 'model']);

it('rejects invalid descriptor interfaces before their constructors run', function (string $category, string $name, string $interface): void {
    $path = $this->directory.'/backend/Definitions/'.$name.'.php';
    $source = file_get_contents($path);
    $source = str_replace(' implements \\Runa\\Contracts\\Modules\\'.$interface, '', $source);
    $source = str_replace('final class '.$name."\n{", 'final class '.$name."\n{\npublic function __construct() { \\Tests\\Fixtures\\ApiCalls::\$constructed++; }", $source);
    file_put_contents($path, $source);
    if ($category === 'apis') {
        expect(fn () => $this->apis->resolve($this->id, $this->contract, '1.0.0'))->toThrow(OperationRejected::class);
    } else {
        $this->http->register();
        $this->getJson($this->url)->assertStatus(503)->assertJsonPath('error.code', 'http_contract_unavailable');
    }
    expect(ApiCalls::$constructed)->toBe(0);
})->with([['apis', 'PublicApi', 'ApiProvider'], ['http', 'Routes', 'HttpProvider']]);

it('attributes HTTP controller construction and invocation to the guarded module request', function (): void {
    $path = $this->directory.'/backend/Http/ReadEntry.php';
    file_put_contents($path, str_replace(
        ['\\Tests\\Fixtures\\ApiCalls::$constructed++;', '\\Tests\\Fixtures\\ApiCalls::$executed++;'],
        ['\\Tests\\Fixtures\\ApiCalls::$constructed++; \\Illuminate\\Support\\Facades\\Log::info("Controller constructed.");', '\\Tests\\Fixtures\\ApiCalls::$executed++; \\Illuminate\\Support\\Facades\\Log::info("Controller invoked.");'],
        file_get_contents($path),
    ));
    $sink = new TestHandler;
    Log::channel()->getLogger()->setHandlers([$sink]);
    $this->http->register();
    app()->instance(OperationAuthorizer::class, new class implements OperationAuthorizer
    {
        public function allows(OperationContext $context, string $permission): bool
        {
            return true;
        }
    });
    $this->guard = new OperationGuard($this->registry, $this->availability, $this->maintenance, app(OperationAuthorizer::class), app(), $this->scope);
    app()->instance(OperationGuard::class, $this->guard);
    $this->getJson($this->url, ['X-Request-ID' => 'module-http-123'])->assertOk();
    expect($sink->getRecords())->toHaveCount(2);
    foreach ($sink->getRecords() as $record) {
        expect($record->extra['origin'])->toBe($this->id)
            ->and($record->extra['request_id'])->toBe('module-http-123')
            ->and($record->extra['correlation_id'])->toBe('module-http-123');
    }
    expect($this->scope->current())->toBeNull();
});

it('resolves account policy before permission validation and unwinds the principal after HTTP', function (): void {
    $principal = new TrustedPrincipal((string) Str::uuid7());
    $policy = new class($principal) implements HttpAccountPolicy
    {
        public bool $deny = true;

        public function __construct(private TrustedPrincipal $principal) {}

        public function principal(Request $request, ?string $module, string $operation, HttpAccess $access): ?TrustedPrincipal
        {
            if ($this->deny) {
                throw new OperationRejected('account_restricted', $module, $operation, 403);
            }

            return $this->principal;
        }
    };
    app()->instance(HttpAccountPolicy::class, $policy);
    $authorizer = new class($principal) implements OperationAuthorizer
    {
        public function __construct(private TrustedPrincipal $principal) {}

        public function allows(OperationContext $context, string $permission): bool
        {
            expect(app(PrincipalScope::class)->current())->toBe($this->principal);

            return true;
        }
    };
    app()->instance(OperationGuard::class, new OperationGuard($this->registry, $this->availability, $this->maintenance, $authorizer, app(), $this->scope));
    $this->http->register();
    $this->postJson($this->url.'?filter=rejected')->assertForbidden()->assertJsonPath('error.code', 'account_restricted');
    expect(ApiCalls::$constructed)->toBe(0);
    $policy->deny = false;
    $this->postJson($this->url.'?filter=rejected')->assertUnprocessable();
    expect(app(PrincipalScope::class)->current())->toBeNull();
    $this->postJson($this->url)->assertOk();
    expect(app(PrincipalScope::class)->current())->toBeNull();
});

it('rejects null principals on authenticated descriptors and stale cached access declarations', function (): void {
    $source = $this->directory.'/backend/Definitions/Routes.php';
    file_put_contents($source, str_replace('ReadEntry::class)', 'ReadEntry::class, \\Runa\\Contracts\\Modules\\HttpAccess::Authenticated)', file_get_contents($source)));
    // Even an incorrectly permissive trusted provider cannot turn authenticated into anonymous.
    app()->instance(HttpAccountPolicy::class, new class implements HttpAccountPolicy
    {
        public function principal(Request $request, ?string $module, string $operation, HttpAccess $access): ?TrustedPrincipal
        {
            return null;
        }
    });
    $this->http->register();
    $this->getJson($this->url.'?filter=rejected')->assertUnauthorized()->assertJsonPath('error.code', 'authentication_required');
    expect(ApiCalls::$constructed)->toBe(0);
    foreach (app('router')->getRoutes()->getRoutes() as $route) {
        if (($route->defaults['_runa_module'] ?? null) === $this->id) {
            $route->defaults('_runa_access', 'public');
        }
    }
    $this->getJson($this->url)->assertStatus(503)->assertJsonPath('error.code', 'http_contract_changed');
    expect(ApiCalls::$constructed)->toBe(0);
});
