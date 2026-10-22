<?php

use Illuminate\Console\Application;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\DB;
use Runa\Contracts\Modules\CommandDefinition;
use Runa\Contracts\Modules\ScheduleDefinition;
use Runa\Support\MaintenanceState;
use Runa\Support\Modules\Api\DenyProtectedOperations;
use Runa\Support\Modules\Api\ModuleDeclarations;
use Runa\Support\Modules\Api\OperationGuard;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Api\OperationScope;
use Runa\Support\Modules\Console\ModuleConsole;
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
use Runa\Support\Modules\Work\WorkDefinitions;
use Symfony\Component\Console\Output\BufferedOutput;
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
    $definition['declarations']->health = [$this->namespace.'\\Definitions\\Health'];
    $definition['declarations']->commands = [$this->namespace.'\\Definitions\\Commands'];
    $definition['declarations']->schedules = [$this->namespace.'\\Definitions\\Schedules'];
    $definition['declarations']->jobs = [$this->namespace.'\\Definitions\\Jobs'];
    $this->sources->write('app', $this->code, $definition);
    file_put_contents($this->directory.'/backend/Definitions/WorkPolicy.php', '<?php namespace '.$this->namespace.'\\Definitions; final class WorkPolicy extends \\Tests\\Fixtures\\TransitionPolicy { public const ID = '.var_export($this->id, true).'; }');
    file_put_contents($this->directory.'/backend/Definitions/Jobs.php', '<?php namespace '.$this->namespace.'\\Definitions; final class Jobs implements \\Runa\\Contracts\\Modules\\JobProvider { public function jobs(): array { return [new \\Runa\\Contracts\\Modules\\JobDefinition("process", Handler::class, Policy::class, \\Tests\\Fixtures\\WorkCalls::$maxAttempts, \\Tests\\Fixtures\\WorkCalls::$retrySeconds, \\Tests\\Fixtures\\WorkCalls::$leaseSeconds, true, "safe_checkpoints_only")]; } }');
    file_put_contents($this->directory.'/backend/Definitions/Handler.php', '<?php namespace '.$this->namespace.'\\Definitions; final class Handler implements \\Runa\\Contracts\\Modules\\WorkHandler { public function __construct() { \\Tests\\Fixtures\\WorkCalls::construct(); } public function handle(\\Runa\\Support\\Modules\\Work\\WorkContext $context, array $payload): void { \\Tests\\Fixtures\\WorkCalls::handle($context, $payload); } }');
    file_put_contents($this->directory.'/backend/Definitions/Policy.php', '<?php namespace '.$this->namespace.'\\Definitions; final class Policy implements \\Runa\\Contracts\\Modules\\WorkPolicy { public function schemaVersion(): int { return \\Tests\\Fixtures\\WorkCalls::$schema; } public function pending(\\Runa\\Contracts\\Modules\\WorkStatus $work, bool $available): \\Runa\\Contracts\\Modules\\WorkDecision { return \\Tests\\Fixtures\\WorkCalls::pending($work, $available); } public function checkpoint(\\Runa\\Contracts\\Modules\\WorkStatus $work): \\Runa\\Contracts\\Modules\\WorkDecision { return \\Tests\\Fixtures\\WorkCalls::checkpoint($work); } }');
    file_put_contents($this->directory.'/backend/Definitions/Commands.php', '<?php namespace '.$this->namespace.'\\Definitions; final class Commands implements \\Runa\\Contracts\\Modules\\CommandProvider { public function commands(): array { return [new \\Runa\\Contracts\\Modules\\CommandDefinition("inspect", "diagnostic", Diagnostic::class), new \\Runa\\Contracts\\Modules\\CommandDefinition("run", "operational", "process")]; } }');
    file_put_contents($this->directory.'/backend/Definitions/Diagnostic.php', '<?php namespace '.$this->namespace.'\\Definitions; final class Diagnostic implements \\Runa\\Contracts\\Modules\\DiagnosticHandler { public function diagnose(array $payload): array { return ["status" => "inspected"]; } }');
    file_put_contents($this->directory.'/backend/Definitions/Schedules.php', '<?php namespace '.$this->namespace.'\\Definitions; final class Schedules implements \\Runa\\Contracts\\Modules\\ScheduleProvider { public function schedules(): array { return [new \\Runa\\Contracts\\Modules\\ScheduleDefinition("tick", "run", "* * * * *", 2), new \\Runa\\Contracts\\Modules\\ScheduleDefinition("recover", "inspect", "*/5 * * * *")]; } }');
    file_put_contents($this->directory.'/backend/Definitions/Health.php', '<?php namespace '.$this->namespace.'\\Definitions; final class Health extends \\Tests\\Fixtures\\HealthPolicy { public const ID = '.var_export($this->id, true).'; }');
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
    $this->console = new ModuleConsole($this->registry, new ModuleDeclarations($this->sources->root, app(), $loader, true), $this->definitions, $this->work, DB::connection());
    $this->transitions = new ModuleTransitions(DB::connection(), $this->registry, $this->lifecycle, app(Encrypter::class), '0.4.0', $this->health);
    $this->queue = 'work-test-'.bin2hex(random_bytes(6));
    config(['queue.default' => 'database', 'queue.connections.database.queue' => $this->queue]);
    $this->registry->synchronize('app');
    $this->setState = function (array $attributes): void {
        DB::table('shared.modules')->where('module_id', $this->id)->update($attributes);
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    };
    ($this->setState)(['enabled' => true]);
    HealthPolicy::$definitions = HealthPolicy::$checks = HealthPolicy::$calls = [];
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

it('registers namespaced commands and rechecks cached commands after disabling', function (): void {
    $artisan = new Application(app(), app('events'), 'test');
    $this->console->registerCommands($artisan);
    $name = 'app:'.$this->code;
    expect($artisan->has($name.':inspect'))->toBeTrue();
    $output = new BufferedOutput;
    expect($this->availability->statuses($this->id, 'process')[$this->id]->available)->toBeTrue();
    expect($artisan->call($name.':run', [], $output))->toBe(0);
    expect(json_decode(trim($output->fetch()), true)['status'])->toBe('pending');
    expect(fn () => $this->console->registerCommands($artisan))->toThrow(RuntimeException::class);
    ($this->setState)(['enabled' => false]);
    expect($artisan->call($name.':run', [], $output))->toBe(1);
    expect($artisan->call($name.':inspect', [], $output))->toBe(0);
    expect($artisan->call($name.':run', ['--payload' => '{invalid'], $output))->toBe(1);
    expect(WorkCalls::$constructed)->toBe(0);
});

it('keeps diagnostics available during maintenance but refuses operational dispatch', function (): void {
    app()->maintenanceMode()->activate(['time' => time()]);
    try {
        expect($this->console->execute($this->id, 'inspect'))->toBe(['status' => 'inspected']);
        expect(fn () => $this->console->execute($this->id, 'run'))->toThrow(OperationRejected::class);
    } finally {
        app()->maintenanceMode()->deactivate();
    }
});

it('rechecks schedules both before enqueue and at real worker start', function (): void {
    $schedule = new Schedule('UTC');
    $this->console->registerSchedules($schedule);
    [$operational, $diagnostic] = $schedule->events();
    expect($operational->description)->toBe('module:'.$this->id.':tick');
    expect($operational->withoutOverlapping)->toBeTrue()->and($operational->expiresAt)->toBe(2);
    expect($diagnostic->evenInMaintenanceMode)->toBeTrue();
    ($this->setState)(['enabled' => false]);
    expect(fn () => $operational->run(app()))->toThrow(OperationRejected::class);
    expect(DB::table('shared.jobs')->where('queue', $this->queue)->count())->toBe(0);
    $diagnostic->run(app());
    expect($diagnostic->exitCode)->toBe(0);
    ($this->setState)(['enabled' => true]);
    $operational->run(app());
    expect($operational->exitCode)->toBe(0);
    expect(DB::table('shared.jobs')->where('queue', $this->queue)->count())->toBe(1);
    ($this->setState)(['enabled' => false]);
    ($this->worker)();
    expect(WorkCalls::$constructed)->toBe(0)->and(WorkCalls::$executed)->toBe(0);
});

it('rejects stale scheduled sources and stale registered commands without loading handlers', function (): void {
    $schedule = new Schedule('UTC');
    $this->console->registerSchedules($schedule);
    $path = $this->directory.'/module.json';
    file_put_contents($path, file_get_contents($path)."\n");
    expect(fn () => $schedule->events()[0]->run(app()))->toThrow(RuntimeException::class);
    expect(fn () => $this->console->execute($this->id, 'inspect'))->toThrow(RuntimeException::class);
    expect(WorkCalls::$constructed)->toBe(0);
});

it('validates command classification cron versions and overlap bounds', function (): void {
    expect(fn () => new CommandDefinition('run', 'unclassified', 'process'))->toThrow(InvalidArgumentException::class);
    expect(fn () => new CommandDefinition('run', 'operational', 'bad:command'))->toThrow(InvalidArgumentException::class);
    expect(fn () => new ScheduleDefinition('tick', 'run', 'invalid'))->toThrow(InvalidArgumentException::class);
    expect(fn () => new ScheduleDefinition('tick', 'run', '* * * * *', 0))->toThrow(InvalidArgumentException::class);
    expect(fn () => new ScheduleDefinition('tick', 'run', '* * * * *', schemaVersion: 2))->toThrow(InvalidArgumentException::class);
});

it('preserves the technical pulse and health recovery schedules', function (): void {
    new Application(app(), app('events'), 'test');
    $events = app(Schedule::class)->events();
    $pulse = array_values(array_filter($events, fn ($event) => str_contains($event->command ?? '', 'runa:runtime:pulse')));
    $health = array_values(array_filter($events, fn ($event) => str_contains($event->command ?? '', 'runa:modules:health')));
    expect($pulse)->toHaveCount(1)->and($health)->toHaveCount(1);
    expect($pulse[0]->evenInMaintenanceMode)->toBeTrue()->and($health[0]->evenInMaintenanceMode)->toBeTrue();
});

it('blocks unhealthy operational commands and schedules while retaining diagnostics and recovery', function (): void {
    $schedule = new Schedule('UTC');
    $this->console->registerSchedules($schedule);
    HealthPolicy::$checks[$this->id] = fn () => false;
    expect(fn () => $this->console->execute($this->id, 'run'))->toThrow(OperationRejected::class);
    expect(fn () => $schedule->events()[0]->run(app()))->toThrow(OperationRejected::class);
    expect($this->console->execute($this->id, 'inspect'))->toBe(['status' => 'inspected']);
    HealthPolicy::$checks[$this->id] = fn () => true;
    $result = $this->console->execute($this->id, 'run');
    ($this->worker)();
    expect($this->work->status($result['work_id'])->status)->toBe('succeeded');
    expect($this->registry->snapshot()->modules[$this->id]->enabled)->toBeTrue();
});

it('honors the scheduler callback overlap mutex and releases it after admission', function (): void {
    $schedule = new Schedule('UTC');
    $this->console->registerSchedules($schedule);
    $event = $schedule->events()[0];
    expect($event->mutex->create($event))->toBeTrue();
    $event->run(app());
    expect(DB::table('shared.jobs')->where('queue', $this->queue)->count())->toBe(0);
    $event->mutex->forget($event);
    $event->run(app());
    expect(DB::table('shared.jobs')->where('queue', $this->queue)->count())->toBe(1);
    expect($event->mutex->exists($event))->toBeFalse();
});
