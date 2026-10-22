<?php

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Database\QueryException;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\EventDefinition;
use Runa\Contracts\Modules\PublicEvent;
use Runa\Support\MaintenanceState;
use Runa\Support\Modules\Api\DenyProtectedOperations;
use Runa\Support\Modules\Api\ModuleDeclarations;
use Runa\Support\Modules\Api\OperationGuard;
use Runa\Support\Modules\Api\OperationScope;
use Runa\Support\Modules\Events\EventDefinitions;
use Runa\Support\Modules\Events\EventSubscriptions;
use Runa\Support\Modules\Events\ModuleEvents;
use Runa\Support\Modules\Events\ModuleIntegrations;
use Runa\Support\Modules\HealthDeadline;
use Runa\Support\Modules\HealthPolicies;
use Runa\Support\Modules\LifecyclePolicies;
use Runa\Support\Modules\ModuleAutoloader;
use Runa\Support\Modules\ModuleAvailability;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleGraph;
use Runa\Support\Modules\ModuleHealth;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\Work\ModuleWork;
use Runa\Support\Modules\Work\WorkDefinitions;
use Tests\Fixtures\EventCalls;
use Tests\Fixtures\EventLog;
use Tests\Fixtures\HealthPolicy;
use Tests\Fixtures\ModuleSources;

beforeEach(function (): void {
    EventCalls::reset();
    $this->sources = new ModuleSources;
    $suffix = bin2hex(random_bytes(6));
    $this->publisher = 'app.fact'.$suffix;
    $this->subscriber = 'app.listen'.$suffix;
    foreach ([$this->publisher, $this->subscriber] as $id) {
        $code = explode('.', $id)[1];
        $namespace = 'App\\Modules\\'.ucfirst($code);
        $manifest = $this->sources->add('app', $code);
        $manifest['declarations']->events = [$namespace.'\\Definitions\\Events'];
        if ($id === $this->subscriber) {
            $manifest['declarations']->jobs = [$namespace.'\\Definitions\\Jobs'];
            $manifest['declarations']->integrations = [$namespace.'\\Definitions\\Integrations'];
            $manifest['dependencies']->optional = (object) [$this->publisher => '^0.1.0'];
        }
        $this->sources->write('app', $code, $manifest);
        $path = $this->sources->root.'/app/modules/'.$code.'/backend/Definitions/';
        foreach (glob(__DIR__.'/../Fixtures/PublicEvents/*.stub') as $stub) {
            file_put_contents($path.basename($stub, '.stub'), str_replace(['MODULE_NAMESPACE', 'PUBLISHER_ID', 'IS_PUBLISHER'], [$namespace, $this->publisher, $id === $this->publisher ? 'true' : 'false'], file_get_contents($stub)));
        }
        file_put_contents($path.'WorkPolicy.php', '<?php namespace '.$namespace.'\\Definitions; final class WorkPolicy extends \\Tests\\Fixtures\\TransitionPolicy {}');
    }
    $this->registry = new ModuleRegistry(DB::connection(), new ModuleDiscovery($this->sources->root));
    $loader = new ModuleAutoloader($this->sources->root, $this->registry, true);
    $declarations = new ModuleDeclarations($this->sources->root, app(), $loader, true);
    $health = new ModuleHealth(DB::connection(), $this->registry, new HealthPolicies($this->sources->root, app(), $loader, true), new HealthDeadline);
    $availability = new ModuleAvailability($this->registry, $health, new LifecyclePolicies($this->sources->root, app(), $loader, true), '0.4.0');
    $this->scope = new OperationScope;
    app()->instance(OperationScope::class, $this->scope);
    $guard = new OperationGuard($this->registry, $availability, new MaintenanceState(app(), DB::connection(), $this->registry, $availability), new DenyProtectedOperations, app(), $this->scope);
    $jobs = new WorkDefinitions($this->registry, $declarations);
    $definitions = new EventDefinitions($declarations, $jobs);
    $this->integrations = new ModuleIntegrations(DB::connection(), $this->registry, $declarations, $guard);
    $this->subscriptions = new EventSubscriptions($this->registry, $definitions, $this->integrations);
    $this->work = new ModuleWork(DB::connection(), $this->registry, $jobs, $guard, $this->scope, app(Encrypter::class), app(Factory::class), $this->subscriptions);
    app()->instance(ModuleWork::class, $this->work);
    $this->logger = new EventLog;
    $this->events = new ModuleEvents(DB::connection(), $this->registry, $definitions, $this->subscriptions, $declarations, $guard, $this->scope, $this->work, $this->logger);
    $this->queue = 'events-test-'.$suffix;
    config(['queue.default' => 'database', 'queue.connections.database.queue' => $this->queue]);
    $this->registry->synchronize('app');
    $this->state = function (string $id, array $values): void {
        DB::table('shared.modules')->where('module_id', $id)->update($values);
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    };
    ($this->state)($this->publisher, ['enabled' => true]);
    ($this->state)($this->subscriber, ['enabled' => true]);
    $this->worker = fn () => app('queue.worker')->runNextJob('database', $this->queue, new WorkerOptions(sleep: 0, maxTries: 1, force: true));
    $this->workId = fn () => DB::table('shared.module_work')->where('module_id', DB::table('shared.modules')->where('module_id', $this->subscriber)->value('id'))->value('public_id');
});
afterEach(function (): void {
    $this->travelBack();
    DB::table('shared.jobs')->where('queue', $this->queue)->delete();
    $ids = DB::table('shared.modules')->whereIn('module_id', [$this->publisher, $this->subscriber])->pluck('id');
    DB::table('shared.module_work')->whereIn('module_id', $ids)->delete();
    DB::table('shared.modules')->whereIn('id', $ids)->delete();
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    $this->sources->remove();
    EventCalls::reset();
});

it('delivers only after outer commit and discards rolled back facts including nested savepoints', function (): void {
    $emit = $this->events->prepare($this->publisher, 'changed', 1, ['public_id' => (string) Str::uuid7()]);
    DB::beginTransaction();
    DB::beginTransaction();
    $emit();
    DB::commit();
    expect(EventCalls::$calls)->toBe([])->and(($this->workId)())->toBeNull();
    DB::commit();
    expect(EventCalls::$calls)->toHaveCount(2)->and(($this->workId)())->not->toBeNull();
    ($this->worker)();
    expect(EventCalls::$calls)->toHaveCount(3);
    EventCalls::$calls = [];
    DB::beginTransaction();
    $this->events->dispatch($this->publisher, 'changed', 1, []);
    DB::rollBack();
    expect(EventCalls::$calls)->toBe([]);
    DB::beginTransaction();
    DB::beginTransaction();
    $this->events->dispatch($this->publisher, 'changed', 1, []);
    DB::rollBack();
    DB::commit();
    expect(EventCalls::$calls)->toBe([]);
});

it('isolates independent sync failures and preserves correlation origin and distinct delivery identities', function (): void {
    EventCalls::$sync = function (): void {
        if (count(EventCalls::$calls) === 1) {
            throw new RuntimeException('private listener payload');
        }
    };
    $event = $this->events->dispatch($this->publisher, 'changed', 1, ['safe' => true]);
    expect(EventCalls::$calls)->toHaveCount(2);
    ($this->worker)();
    expect(EventCalls::$calls)->toHaveCount(3)->and(array_unique(array_column(EventCalls::$calls, 2)))->toHaveCount(3);
    foreach (EventCalls::$calls as $call) {
        expect($call[1])->toBe($event->id)->and($call[3])->toBe($this->publisher);
    }
    expect($this->work->status(($this->workId)())->correlationId)->toBe($event->correlationId)->and($this->scope->current())->toBeNull();
});

it('retries only the failed idempotent subscriber and deduplicates repeated async delivery', function (): void {
    $event = $this->events->dispatch($this->publisher, 'changed', 1, []);
    $id = ($this->workId)();
    expect($this->work->dispatchEvent($this->subscriber, 'queued', $event))->toBe($id);
    expect(DB::table('shared.jobs')->where('queue', $this->queue)->count())->toBe(1);
    EventCalls::$async = fn () => throw new RuntimeException('ambiguous effect reconciled by idempotent subscriber');
    ($this->worker)();
    expect($this->work->status($id)->status)->toBe('failed');
    $key = EventCalls::$calls[2][2];
    EventCalls::$async = null;
    $this->work->recover($id);
    $this->travel(3)->seconds();
    ($this->worker)();
    expect($this->work->status($id)->status)->toBe('succeeded')->and(EventCalls::$calls)->toHaveCount(4);
    expect(EventCalls::$calls[3][2])->toBe($key)->and(array_column(EventCalls::$calls, 0))->toBe(['first', 'second', 'async', 'async']);
    expect($this->work->dispatchEvent($this->subscriber, 'queued', $event))->toBe($id);
    expect(DB::table('shared.jobs')->where('queue', $this->queue)->count())->toBe(0);
});

it('blocks disabled queued listeners before handler construction and permits bounded recovery after reenabling', function (): void {
    $this->events->dispatch($this->publisher, 'changed', 1, []);
    EventCalls::$constructed = 0;
    ($this->state)($this->subscriber, ['enabled' => false]);
    ($this->worker)();
    expect(EventCalls::$constructed)->toBe(0)->and(EventCalls::$calls)->toHaveCount(2);
    ($this->state)($this->subscriber, ['enabled' => true]);
    $this->travel(3)->seconds();
    ($this->worker)();
    expect($this->work->status(($this->workId)())->status)->toBe('succeeded')->and(EventCalls::$constructed)->toBe(1);
});

it('toggles optional integration independently and rejects stale or invalid config writes', function (): void {
    EventCalls::$integration = true;
    $generation = $this->registry->snapshot()->generation;
    $next = $this->integrations->configure($this->subscriber, 'audit', false, ['format' => 'full'], $generation);
    expect($next)->toBe($generation + 1);
    expect(fn () => $this->integrations->configure($this->subscriber, 'audit', true, ['format' => 'compact'], $generation))->toThrow(RuntimeException::class);
    expect(fn () => $this->integrations->configure($this->subscriber, 'audit', true, ['format' => 'invalid'], $next))->toThrow(RuntimeException::class);
    $this->events->dispatch($this->publisher, 'changed', 1, []);
    expect(EventCalls::$calls)->toBe([])->and(($this->workId)())->toBeNull();
    foreach ([$this->publisher, $this->subscriber] as $id) {
        expect($this->registry->snapshot()->modules[$id]->enabled)->toBeTrue();
    }
    expect((new ModuleGraph($this->registry->snapshot(), '0.4.0'))->preview('enable', $this->subscriber)->graphReady())->toBeTrue();
    expect($this->integrations->metadata($this->subscriber)['audit']['config'])->toBe(['format' => 'full']);
});

it('rechecks integration toggle and both sides at actual async start', function (): void {
    EventCalls::$integration = true;
    $this->events->dispatch($this->publisher, 'changed', 1, []);
    $generation = $this->registry->snapshot()->generation;
    $this->integrations->configure($this->subscriber, 'audit', false, ['format' => 'compact'], $generation);
    EventCalls::$constructed = 0;
    ($this->worker)();
    expect(EventCalls::$constructed)->toBe(0);
    $this->integrations->configure($this->subscriber, 'audit', true, ['format' => 'compact'], $this->registry->snapshot()->generation);
    ($this->state)($this->publisher, ['enabled' => false]);
    $this->travel(3)->seconds();
    ($this->worker)();
    expect(EventCalls::$constructed)->toBe(0)->and($this->registry->snapshot()->modules[$this->subscriber]->enabled)->toBeTrue();
});

it('keeps optional absence independent while required Admin Audit dependency blocks activation', function (): void {
    EventCalls::$integration = true;
    ($this->state)($this->publisher, ['source_present' => false]);
    expect(fn () => $this->integrations->requireAvailable($this->subscriber, 'audit'))->toThrow(RuntimeException::class);
    expect((new ModuleGraph($this->registry->snapshot(), '0.4.0'))->preview('enable', $this->subscriber)->graphReady())->toBeTrue();
    $code = explode('.', $this->subscriber)[1];
    $path = $this->sources->root.'/app/modules/'.$code.'/module.json';
    $manifest = json_decode(file_get_contents($path));
    $manifest->dependencies->required = (object) [$this->publisher => '^0.1.0'];
    $manifest->dependencies->optional = new stdClass;
    file_put_contents($path, json_encode($manifest));
    ($this->state)($this->subscriber, ['manifest' => json_encode($manifest)]);
    expect((new ModuleGraph($this->registry->snapshot(), '0.4.0'))->preview('enable', $this->subscriber)->graphReady())->toBeFalse();
});

it('refuses stale listener source and maintenance at real queue execution', function (): void {
    $this->events->dispatch($this->publisher, 'changed', 1, []);
    EventCalls::$constructed = 0;
    app()->maintenanceMode()->activate(['time' => time()]);
    try {
        ($this->worker)();
        expect(EventCalls::$constructed)->toBe(0);
        expect(fn () => $this->events->dispatch($this->publisher, 'changed', 1, []))->toThrow(RuntimeException::class);
    } finally {
        app()->maintenanceMode()->deactivate();
    }
    $path = $this->sources->root.'/app/modules/'.explode('.', $this->subscriber)[1].'/module.json';
    file_put_contents($path, file_get_contents($path)."\n");
    $this->travel(3)->seconds();
    ($this->worker)();
    expect(EventCalls::$constructed)->toBe(0);
});

it('validates event versions public payloads and stable ids before dispatch', function (): void {
    expect(fn () => new EventDefinition('changed', 0))->toThrow(InvalidArgumentException::class);
    expect(fn () => new PublicEvent('invalid', $this->publisher, 'changed', 1, 'safe', []))->toThrow(InvalidArgumentException::class);
    expect(fn () => $this->events->dispatch($this->publisher, 'unknown', 1, []))->toThrow(RuntimeException::class);
    expect(fn () => $this->events->dispatch($this->publisher, 'changed', 2, []))->toThrow(RuntimeException::class);
    expect(fn () => $this->events->dispatch($this->publisher, 'changed', 1, ['private' => new stdClass]))->toThrow(RuntimeException::class);
    expect(fn () => $this->events->dispatch($this->publisher, 'changed', 1, [str_repeat('x', 60001)]))->toThrow(InvalidArgumentException::class);
});

it('rechecks between independent listeners when the first listener changes lifecycle state', function (): void {
    EventCalls::$sync = function (): void {
        ($this->state)($this->subscriber, ['enabled' => false]);
    };
    $this->events->dispatch($this->publisher, 'changed', 1, []);
    expect(EventCalls::$calls)->toHaveCount(1)->and(EventCalls::$constructed)->toBe(1)->and(($this->workId)())->toBeNull();
    expect($this->registry->snapshot()->modules[$this->publisher]->enabled)->toBeTrue();
});

it('rejects generic job dispatch to a listener bound target', function (): void {
    expect(fn () => $this->work->dispatch($this->subscriber, 'receive', []))->toThrow(RuntimeException::class);
    expect(($this->workId)())->toBeNull();
});

it('retains optional integration state during source synchronization without activating either module', function (): void {
    $this->integrations->configure($this->subscriber, 'audit', false, ['format' => 'full'], $this->registry->snapshot()->generation);
    ($this->state)($this->subscriber, ['enabled' => false]);
    $this->registry->synchronize('app');
    expect($this->integrations->metadata($this->subscriber)['audit']['enabled'])->toBeFalse();
    expect($this->integrations->metadata($this->subscriber)['audit']['config'])->toBe(['format' => 'full']);
    expect($this->registry->snapshot()->modules[$this->subscriber]->enabled)->toBeFalse();
});

it('keeps the committed origin successful if enqueue fails and redacts listener errors from logs', function (): void {
    EventCalls::$sync = fn () => throw new RuntimeException('private-exception');
    config(['queue.default' => 'sync']);
    $originRuns = 0;
    DB::transaction(function () use (&$originRuns): void {
        $originRuns++;
        DB::table('shared.modules')->where('module_id', $this->publisher)->update(['integration_values' => '{"origin_marker":{"enabled":false,"config":{}}}']);
        $this->events->dispatch($this->publisher, 'changed', 1, ['public_value' => 'private-payload']);
    });
    expect($this->logger->entries)->toHaveCount(3);
    foreach ($this->logger->entries as [$message, $context]) {
        expect($context['listener_key'])->toBeIn(['first', 'second', 'queued']);
        expect(json_encode([$message, $context]))->not->toContain('private-exception', 'private-payload');
    }
    expect($originRuns)->toBe(1)->and(EventCalls::$calls)->toHaveCount(2)->and(($this->workId)())->toBeNull();
    expect(DB::table('shared.modules')->where('module_id', $this->publisher)->value('integration_values'))->toContain('origin_marker');
});

it('rejects conflicting envelopes sharing a delivery identity and enforces actual database constraints', function (): void {
    $event = $this->events->dispatch($this->publisher, 'changed', 1, ['value' => 'one']);
    $conflict = new PublicEvent($event->id, $event->module, $event->event, $event->version, $event->correlationId, ['value' => 'two']);
    expect(fn () => $this->work->dispatchEvent($this->subscriber, 'queued', $conflict))->toThrow(RuntimeException::class);
    expect(DB::table('shared.jobs')->where('queue', $this->queue)->count())->toBe(1);
    expect(fn () => DB::transaction(fn () => DB::table('shared.module_work')->where('public_id', ($this->workId)())->update(['delivery_key' => null])))->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('shared.modules')->where('module_id', $this->subscriber)->update(['integration_values' => '[]'])))->toThrow(QueryException::class);
});

it('rechecks a toggle changed during the final operational health check before enqueue', function (): void {
    EventCalls::$integration = true;
    $code = explode('.', $this->subscriber)[1];
    $namespace = 'App\\Modules\\'.ucfirst($code);
    $path = $this->sources->root.'/app/modules/'.$code;
    $manifest = json_decode(file_get_contents($path.'/module.json'));
    $manifest->declarations->health = [$namespace.'\\Definitions\\Health'];
    file_put_contents($path.'/module.json', json_encode($manifest));
    file_put_contents($path.'/backend/Definitions/Health.php', '<?php namespace '.$namespace.'\\Definitions; final class Health extends \\Tests\\Fixtures\\HealthPolicy { public const ID = '.var_export($this->subscriber, true).'; }');
    $this->registry->synchronize('app');
    HealthPolicy::$definitions[$this->subscriber] = ['operations' => ['receive']];
    HealthPolicy::$checks[$this->subscriber] = function (): bool {
        $this->integrations->configure($this->subscriber, 'audit', false, ['format' => 'compact'], $this->registry->snapshot()->generation);

        return true;
    };
    try {
        $this->events->dispatch($this->publisher, 'changed', 1, []);
        expect(EventCalls::$calls)->toHaveCount(2)->and(($this->workId)())->toBeNull();
        expect($this->integrations->metadata($this->subscriber)['audit']['enabled'])->toBeFalse();
        expect($this->registry->snapshot()->modules[$this->subscriber]->enabled)->toBeTrue();
    } finally {
        unset(HealthPolicy::$definitions[$this->subscriber], HealthPolicy::$checks[$this->subscriber], HealthPolicy::$calls[$this->subscriber]);
    }
});

it('fails closed on malformed persisted toggle values', function (): void {
    EventCalls::$integration = true;
    ($this->state)($this->subscriber, ['integration_values' => '{"audit":{"enabled":"false","config":{"format":"compact"}}}']);
    expect(fn () => $this->integrations->requireAvailable($this->subscriber, 'audit'))->toThrow(RuntimeException::class);
    $this->events->dispatch($this->publisher, 'changed', 1, []);
    expect(EventCalls::$constructed)->toBe(0)->and(($this->workId)())->toBeNull();
    expect($this->registry->snapshot()->modules[$this->subscriber]->enabled)->toBeTrue();
});
