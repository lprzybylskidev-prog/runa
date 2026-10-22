<?php

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\Preparation;
use Runa\Contracts\Modules\WorkDecision;
use Runa\Contracts\Modules\WorkStatus;
use Runa\Support\MaintenanceState;
use Runa\Support\Modules\Api\DenyProtectedOperations;
use Runa\Support\Modules\Api\ModuleDeclarations;
use Runa\Support\Modules\Api\OperationGuard;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Api\OperationScope;
use Runa\Support\Modules\HealthDeadline;
use Runa\Support\Modules\HealthPolicies;
use Runa\Support\Modules\LifecyclePolicies;
use Runa\Support\Modules\ModuleAutoloader;
use Runa\Support\Modules\ModuleAvailability;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleHealth;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\ModuleTransitions;
use Runa\Support\Modules\Work\ModuleWork;
use Runa\Support\Modules\Work\WorkContext;
use Runa\Support\Modules\Work\WorkDefinitions;
use Symfony\Component\Process\Process;
use Tests\Fixtures\HealthPolicy;
use Tests\Fixtures\ModuleSources;
use Tests\Fixtures\TransitionPolicy;
use Tests\Fixtures\WorkCalls;

beforeEach(function (): void {
    $this->sources = new ModuleSources;
    $this->code = 'work'.bin2hex(random_bytes(6));
    $this->id = 'app.'.$this->code;
    $this->namespace = 'App\\Modules\\'.ucfirst($this->code);
    $this->directory = $this->sources->root.'/app/modules/'.$this->code;
    $definition = $this->sources->add('app', $this->code);
    $definition['declarations']->jobs = [$this->namespace.'\\Definitions\\Jobs'];
    $this->sources->write('app', $this->code, $definition);
    file_put_contents($this->directory.'/backend/Definitions/WorkPolicy.php', '<?php namespace '.$this->namespace.'\\Definitions; final class WorkPolicy extends \\Tests\\Fixtures\\TransitionPolicy { public const ID = '.var_export($this->id, true).'; }');
    file_put_contents($this->directory.'/backend/Definitions/Jobs.php', '<?php namespace '.$this->namespace.'\\Definitions; final class Jobs implements \\Runa\\Contracts\\Modules\\JobProvider { public function jobs(): array { return [new \\Runa\\Contracts\\Modules\\JobDefinition("process", Handler::class, Policy::class, \\Tests\\Fixtures\\WorkCalls::$maxAttempts, \\Tests\\Fixtures\\WorkCalls::$retrySeconds, \\Tests\\Fixtures\\WorkCalls::$leaseSeconds, true, "safe_checkpoints_only")]; } }');
    file_put_contents($this->directory.'/backend/Definitions/Handler.php', '<?php namespace '.$this->namespace.'\\Definitions; final class Handler implements \\Runa\\Contracts\\Modules\\WorkHandler { public function __construct() { \\Tests\\Fixtures\\WorkCalls::construct(); } public function handle(\\Runa\\Support\\Modules\\Work\\WorkContext $context, array $payload): void { \\Tests\\Fixtures\\WorkCalls::handle($context, $payload); } }');
    file_put_contents($this->directory.'/backend/Definitions/Policy.php', '<?php namespace '.$this->namespace.'\\Definitions; final class Policy implements \\Runa\\Contracts\\Modules\\WorkPolicy { public function schemaVersion(): int { return \\Tests\\Fixtures\\WorkCalls::$schema; } public function pending(\\Runa\\Contracts\\Modules\\WorkStatus $work, bool $available): \\Runa\\Contracts\\Modules\\WorkDecision { return \\Tests\\Fixtures\\WorkCalls::pending($work, $available); } public function checkpoint(\\Runa\\Contracts\\Modules\\WorkStatus $work): \\Runa\\Contracts\\Modules\\WorkDecision { return \\Tests\\Fixtures\\WorkCalls::checkpoint($work); } }');
    $this->registry = new ModuleRegistry(DB::connection(), new ModuleDiscovery($this->sources->root));
    $loader = new ModuleAutoloader($this->sources->root, $this->registry, true);
    $this->lifecycle = new LifecyclePolicies($this->sources->root, app(), $loader, true);
    $this->health = new ModuleHealth(DB::connection(), $this->registry, new HealthPolicies($this->sources->root, app(), $loader, true), new HealthDeadline);
    $this->availability = new ModuleAvailability($this->registry, $this->health, $this->lifecycle, '0.4.0');
    $maintenance = new MaintenanceState(app(), DB::connection(), $this->registry, $this->availability);
    $this->scope = new OperationScope;
    $this->guard = new OperationGuard($this->registry, $this->availability, $maintenance, new DenyProtectedOperations, app(), $this->scope);
    $this->definitions = new WorkDefinitions($this->registry, new ModuleDeclarations($this->sources->root, app(), $loader, true));
    $this->work = new ModuleWork(DB::connection(), $this->registry, $this->definitions, $this->guard, $this->scope, app(Encrypter::class), app(Factory::class));
    app()->instance(ModuleWork::class, $this->work);
    $this->transitions = new ModuleTransitions(DB::connection(), $this->registry, $this->lifecycle, app(Encrypter::class), '0.4.0', $this->health);
    $this->queue = 'work-test-'.bin2hex(random_bytes(6));
    config(['queue.default' => 'database', 'queue.connections.database.queue' => $this->queue]);
    $this->registry->synchronize('app');
    $this->setState = function (array $attributes): void {
        DB::table('shared.modules')->where('module_id', $this->id)->update($attributes);
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    };
    ($this->setState)(['enabled' => true]);
    WorkCalls::$constructed = WorkCalls::$executed = 0;
    WorkCalls::$pending = WorkCalls::$checkpoint = WorkCalls::$handle = WorkCalls::$construct = null;
    WorkCalls::$maxAttempts = 3;
    WorkCalls::$retrySeconds = 2;
    WorkCalls::$leaseSeconds = 30;
    WorkCalls::$schema = 1;
    TransitionPolicy::$prepare = TransitionPolicy::$compensate = TransitionPolicy::$revisions = TransitionPolicy::$calls = [];
    $this->worker = function (): void {
        app('queue.worker')->runNextJob('database', $this->queue, new WorkerOptions(sleep: 0, maxTries: 1, force: true));
    };
});

afterEach(function (): void {
    $this->travelBack();
    DB::table('shared.jobs')->where('queue', $this->queue)->delete();
    DB::table('shared.module_work')->where('module_id', DB::table('shared.modules')->where('module_id', $this->id)->value('id'))->delete();
    DB::table('shared.module_transitions')->where('target', $this->id)->delete();
    DB::table('shared.modules')->where('module_id', $this->id)->delete();
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    $this->sources->remove();
});

it('executes the database envelope through the real worker with safe metadata and unwound correlation scope', function (): void {
    $parent = $this->guard->enter($this->id, new ApiOperation('origin'));
    $id = $this->scope->run($parent, fn () => $this->work->dispatch($this->id, 'process', ['private' => 'sensitive-test-payload']));
    expect(DB::table('shared.jobs')->where('queue', $this->queue)->count())->toBe(1);
    $payload = DB::table('shared.jobs')->where('queue', $this->queue)->value('payload');
    expect($payload)->toContain('ModuleJob')->not->toContain('sensitive-test-payload')->not->toContain($this->namespace);
    expect(DB::table('shared.module_work')->where('public_id', $id)->value('payload'))->not->toContain('sensitive-test-payload');
    WorkCalls::$handle = function (WorkContext $context, array $payload) use ($id, $parent): void {
        expect($payload)->toBe(['private' => 'sensitive-test-payload']);
        expect($this->scope->current()->operationId)->toBe($id)->and($this->scope->current()->correlationId)->toBe($parent->correlationId)->and($this->scope->current()->originModule)->toBe($this->id);
        expect($context->checkpoint('unit_finished')->status)->toBe('running');
    };
    ($this->worker)();
    expect($this->work->status($id)->status)->toBe('succeeded')->and(WorkCalls::$executed)->toBe(1)->and($this->scope->current())->toBeNull();
    expect($this->work->execute($id))->toBeNull()->and(WorkCalls::$executed)->toBe(1);
});

it('atomically rolls back the work row and queue envelope with the caller transaction', function (): void {
    DB::beginTransaction();
    $id = $this->work->dispatch($this->id, 'process', []);
    expect(DB::table('shared.jobs')->where('queue', $this->queue)->count())->toBe(1);
    DB::rollBack();
    expect(DB::table('shared.module_work')->where('public_id', $id)->exists())->toBeFalse()->and(DB::table('shared.jobs')->where('queue', $this->queue)->count())->toBe(0);
});

it('rejects new dispatch and pending start after disable and never resurrects cancellation after reenable', function (): void {
    $id = $this->work->dispatch($this->id, 'process', []);
    expect($this->transitions->apply($this->transitions->preview('disable', $this->id))->status)->toBe('succeeded');
    expect($this->work->status($id)->cancellationRequested)->toBeTrue();
    expect(fn () => $this->work->dispatch($this->id, 'process', []))->toThrow(OperationRejected::class);
    expect($this->transitions->apply($this->transitions->preview('enable', $this->id))->status)->toBe('succeeded');
    ($this->worker)();
    expect($this->work->status($id)->status)->toBe('cancelled')->and(WorkCalls::$constructed)->toBe(0);
});

it('honors a custom waiting lifecycle policy until running work reaches its safe cancellation checkpoint', function (): void {
    $id = $this->work->dispatch($this->id, 'process', []);
    TransitionPolicy::$prepare[$this->id] = fn () => new Preparation(count($this->work->active($this->id)) > 0 ? 'waiting' : 'ready', code: 'custom_drain');
    WorkCalls::$handle = function (WorkContext $context): void {
        $result = $this->transitions->apply($this->transitions->preview('disable', $this->id));
        expect($result->status)->toBe('pending')->and($this->registry->snapshot()->modules[$this->id]->enabled)->toBeTrue();
        $this->transitionId = $result->operationId;
        $context->checkpoint('committed_unit');
        throw new LogicException('Unreachable unsafe continuation.');
    };
    ($this->worker)();
    expect($this->work->status($id)->status)->toBe('cancelled')->and($this->work->status($id)->progress)->toBe('safe_stop');
    expect($this->transitions->resume($this->transitionId)->status)->toBe('succeeded');
});

it('lets the module finish an already admitted atomic unit and report cooperative limits', function (): void {
    $id = $this->work->dispatch($this->id, 'process', []);
    WorkCalls::$checkpoint = fn (WorkStatus $status) => new WorkDecision('run', 'finish_atomic_tail');
    WorkCalls::$handle = function (WorkContext $context): void {
        $this->work->requestCancellation($context->workId);
        expect($context->checkpoint('atomic_tail_finished')->cancellationRequested)->toBeTrue();
    };
    ($this->worker)();
    expect($this->work->status($id)->status)->toBe('succeeded')->and($this->work->status($id)->cancellationRequested)->toBeTrue();
});

it('enforces custom bounded availability retries without constructing the handler', function (): void {
    $id = $this->work->dispatch($this->id, 'process', []);
    ($this->setState)(['enabled' => false]);
    WorkCalls::$pending = function (WorkStatus $status, bool $available): WorkDecision {
        expect($available)->toBeFalse();

        return new WorkDecision('retry', 'custom_wait', 3);
    };
    for ($attempt = 0; $attempt < 3; $attempt++) {
        ($this->worker)();
        $this->travel(4)->seconds();
    }
    expect($this->work->status($id)->status)->toBe('refused')->and($this->work->status($id)->attempts)->toBe(3)->and(WorkCalls::$constructed)->toBe(0);
});

it('can release at a running checkpoint and resume only through fresh admission', function (): void {
    $id = $this->work->dispatch($this->id, 'process', []);
    WorkCalls::$checkpoint = fn () => new WorkDecision('retry', 'module_rescheduled');
    WorkCalls::$handle = fn (WorkContext $context) => $context->checkpoint('unit_done');
    ($this->worker)();
    expect($this->work->status($id)->status)->toBe('pending');
    $this->travel(3)->seconds();
    WorkCalls::$handle = null;
    ($this->worker)();
    expect($this->work->status($id)->status)->toBe('succeeded')->and(WorkCalls::$executed)->toBe(2);
});

it('expires crashed leases with evidence and requires explicit module reconciliation before recovery', function (): void {
    $id = $this->work->dispatch($this->id, 'process', []);
    DB::table('shared.module_work')->where('public_id', $id)->update(['status' => 'running', 'attempts' => 1, 'lease_token' => (string) Str::uuid7(), 'lease_until' => now()->subSecond()]);
    expect($this->work->status($id)->status)->toBe('recovery_required')->and($this->work->status($id)->progress)->toBe('lease_expired');
    ($this->worker)();
    expect(WorkCalls::$constructed)->toBe(0);
    WorkCalls::$pending = fn () => new WorkDecision('run', 'unreconciled');
    expect(fn () => $this->work->recover($id))->toThrow(RuntimeException::class);
    WorkCalls::$pending = fn () => new WorkDecision('retry', 'reconciled_by_owner');
    expect($this->work->recover($id)->status)->toBe('pending');
    $this->travel(3)->seconds();
    WorkCalls::$pending = null;
    ($this->worker)();
    expect($this->work->status($id)->status)->toBe('succeeded');
});

it('does not let an expired owner publish success or continue from its old checkpoint', function (): void {
    $id = $this->work->dispatch($this->id, 'process', []);
    WorkCalls::$handle = function (WorkContext $context): void {
        $this->travel(31)->seconds();
        $context->checkpoint('late_unit');
    };
    ($this->worker)();
    expect($this->work->status($id)->status)->toBe('recovery_required')->and($this->work->status($id)->progress)->toBe('lease_expired');
});

it('reports failed work without exposing exceptions or blindly retrying ambiguous effects', function (): void {
    $id = $this->work->dispatch($this->id, 'process', ['secret' => 'private-value']);
    WorkCalls::$handle = fn () => throw new RuntimeException('sensitive exception');
    ($this->worker)();
    expect($this->work->status($id)->status)->toBe('failed')->and(json_encode($this->work->status($id)))->not->toContain('private-value')->not->toContain('sensitive exception');
    expect($this->scope->current())->toBeNull();
    expect($this->work->execute($id))->toBeNull()->and(WorkCalls::$executed)->toBe(1);
});

it('rejects policy retry bounds and source drift before handler construction', function (string $mode): void {
    $id = $this->work->dispatch($this->id, 'process', []);
    if ($mode === 'bounds') {
        WorkCalls::$pending = fn () => new WorkDecision('retry', 'invalid_delay', 1);
    } elseif ($mode === 'schema') {
        WorkCalls::$schema = 2;
    } else {
        file_put_contents($this->directory.'/module.json', "\n", FILE_APPEND);
    }
    ($this->worker)();
    expect($this->work->status($id)->status)->toBe('refused')->and(WorkCalls::$constructed)->toBe(0);
})->with(['bounds', 'schema', 'source']);

it('rejects raw module dispatch and centrally deletes unmanaged queued objects before deserialization', function (): void {
    $class = $this->namespace.'\\Definitions\\Unmanaged';
    file_put_contents($this->directory.'/backend/Definitions/Unmanaged.php', '<?php namespace '.$this->namespace.'\\Definitions; final class Unmanaged { public function handle(): void { \\Tests\\Fixtures\\WorkCalls::$executed++; } public function __wakeup(): void { \\Tests\\Fixtures\\WorkCalls::$constructed++; } }');
    $loader = new ModuleAutoloader($this->sources->root, $this->registry, true);
    $loader->load($class);
    $job = new $class;
    expect(fn () => app('queue')->connection('database')->push($job))->toThrow(RuntimeException::class, 'Module jobs must use ModuleWork');
    $payload = ['uuid' => (string) Str::uuid7(), 'displayName' => $class, 'job' => 'Illuminate\\Queue\\CallQueuedHandler@call', 'maxTries' => 1, 'data' => ['commandName' => $class, 'command' => serialize($job)]];
    app('queue')->connection('database')->pushRaw(json_encode($payload, JSON_THROW_ON_ERROR), $this->queue);
    ($this->worker)();
    expect(WorkCalls::$constructed)->toBe(0)->and(WorkCalls::$executed)->toBe(0)->and(DB::table('shared.jobs')->where('queue', $this->queue)->count())->toBe(0);
});

it('rejects invalid payloads and queue visibility bounds without writing work', function (): void {
    expect(fn () => $this->work->dispatch($this->id, 'process', ['object' => new stdClass]))->toThrow(RuntimeException::class);
    WorkCalls::$leaseSeconds = 90;
    expect(fn () => $this->work->dispatch($this->id, 'process', []))->toThrow(RuntimeException::class);
    expect($this->work->active($this->id))->toBe([]);
});

it('runs an independent worker process and preserves crash evidence across process death', function (string $mode): void {
    $id = $this->work->dispatch($this->id, 'process', []);
    $process = new Process([PHP_BINARY, 'runa/tests/Fixtures/work-worker.php', $this->sources->root, $this->queue, $mode], base_path(), ['APP_ENV' => 'testing', 'DB_DATABASE' => DB::connection()->getDatabaseName(), 'LARAVEL_STORAGE_PATH' => storage_path()]);
    $process->setTimeout(20);
    expect($process->run())->toBe($mode === 'crash' ? 23 : 0, $process->getErrorOutput().$process->getOutput());
    if ($mode === 'crash') {
        expect($this->work->status($id)->status)->toBe('running');
        $this->travel(31)->seconds();
        expect($this->work->status($id)->status)->toBe('recovery_required')->and($this->work->status($id)->progress)->toBe('lease_expired');
        expect($this->work->execute($id))->toBeNull()->and(WorkCalls::$executed)->toBe(0);
    } else {
        expect($this->work->status($id)->status)->toBe('succeeded')->and($this->work->status($id)->attempts)->toBe(1);
    }
})->with(['success', 'crash']);

it('rechecks the generation after module policy and prevents a state change from authorizing stale execution', function (): void {
    $id = $this->work->dispatch($this->id, 'process', []);
    WorkCalls::$pending = function (): WorkDecision {
        ($this->setState)(['enabled' => false]);

        return new WorkDecision('run', 'stale_ready');
    };
    ($this->worker)();
    expect($this->work->status($id)->status)->toBe('refused')->and(WorkCalls::$constructed)->toBe(0);
});

it('fences duplicate delivery and refuses checkpoints inside atomic database units', function (): void {
    $id = $this->work->dispatch($this->id, 'process', []);
    WorkCalls::$handle = function (WorkContext $context) use ($id): void {
        expect($this->work->execute($id))->toBeNull()->and(WorkCalls::$executed)->toBe(1);
        DB::beginTransaction();
        try {
            expect(fn () => $context->checkpoint('inside_transaction'))->toThrow(RuntimeException::class, 'outside an atomic');
        } finally {
            DB::rollBack();
        }
    };
    ($this->worker)();
    expect($this->work->status($id)->status)->toBe('succeeded')->and(WorkCalls::$executed)->toBe(1);
});

it('publishes safe active-work capabilities and preview counts without mutating a preview', function (): void {
    $id = $this->work->dispatch($this->id, 'process', []);
    $before = DB::table('shared.module_work')->where('public_id', $id)->first();
    expect($this->transitions->preview('disable', $this->id)->activeWork)->toBe([$this->id => ['pending' => 1]]);
    expect(DB::table('shared.module_work')->where('public_id', $id)->first())->toEqual($before);
    expect($this->work->active($this->id)[0]->cancellable)->toBeTrue()->and($this->work->status($id)->limitations)->toBe('safe_checkpoints_only');
});

it('drains cancelled work without evaluating per-use health under the transition fence', function (): void {
    $definition = (array) json_decode(file_get_contents($this->directory.'/module.json'), flags: JSON_THROW_ON_ERROR);
    $definition['declarations']->health = [$this->namespace.'\\Definitions\\Checks'];
    $this->sources->write('app', $this->code, $definition);
    file_put_contents($this->directory.'/backend/Definitions/Checks.php', '<?php namespace '.$this->namespace.'\\Definitions; final class Checks extends \\Tests\\Fixtures\\HealthPolicy { public const ID = '.var_export($this->id, true).'; }');
    $this->registry->synchronize('app');
    $this->health->diagnose($this->id);
    $enqueue = $this->work->prepareDispatch($this->id, 'process', []);
    $id = DB::transaction(fn () => $enqueue());
    TransitionPolicy::$prepare[$this->id] = fn () => new Preparation($this->work->active($this->id) === [] ? 'ready' : 'waiting');
    $result = $this->transitions->apply($this->transitions->preview('disable', $this->id));
    expect($result->status)->toBe('pending');
    $generation = $this->registry->snapshot()->generation;
    $calls = HealthPolicy::$calls[$this->id];
    ($this->worker)();
    expect($this->work->status($id)->status)->toBe('cancelled')->and($this->registry->snapshot()->generation)->toBe($generation)->and(HealthPolicy::$calls[$this->id])->toBe($calls);
    expect($this->transitions->resume($result->operationId)->status)->toBe('succeeded');
});

it('rechecks lease and cancellation after handler construction before invoking business work', function (string $mode): void {
    $id = $this->work->dispatch($this->id, 'process', []);
    WorkCalls::$construct = function () use ($id, $mode): void {
        if ($mode === 'cancel') {
            $this->work->requestCancellation($id);
        } else {
            $this->travel(31)->seconds();
        }
    };
    ($this->worker)();
    expect(WorkCalls::$executed)->toBe(0)->and($this->work->status($id)->status)->toBe($mode === 'cancel' ? 'refused' : 'recovery_required');
})->with(['cancel', 'expired']);

it('retains recovery evidence when cancellation races a checkpoint policy decision', function (): void {
    $id = $this->work->dispatch($this->id, 'process', []);
    WorkCalls::$checkpoint = function () use ($id): WorkDecision {
        $this->work->requestCancellation($id);

        return new WorkDecision('run', 'stale_continue');
    };
    WorkCalls::$handle = fn (WorkContext $context) => $context->checkpoint('next_unit');
    ($this->worker)();
    expect($this->work->status($id)->status)->toBe('recovery_required')->and($this->work->status($id)->progress)->toBe('checkpoint_recheck_required');
    WorkCalls::$pending = fn () => new WorkDecision('cancel', 'owner_reconciled');
    expect($this->work->recover($id)->status)->toBe('cancelled');
    expect($this->work->execute((string) Str::uuid7()))->toBeNull();
});

it('rejects stale prepared dispatch inside the caller transaction without a work row or envelope', function (): void {
    $enqueue = $this->work->prepareDispatch($this->id, 'process', []);
    ($this->setState)(['enabled' => false]);
    expect(fn () => DB::transaction(fn () => $enqueue()))->toThrow(OperationRejected::class);
    expect($this->work->active($this->id))->toBe([])->and(DB::table('shared.jobs')->where('queue', $this->queue)->count())->toBe(0);
});
