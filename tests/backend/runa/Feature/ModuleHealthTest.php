<?php

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Runa\Contracts\Modules\Preparation;
use Runa\Support\MaintenanceState;
use Runa\Support\Modules\HealthDeadline;
use Runa\Support\Modules\HealthPolicies;
use Runa\Support\Modules\LifecyclePolicies;
use Runa\Support\Modules\ModuleAutoloader;
use Runa\Support\Modules\ModuleAvailability;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleHealth;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\ModuleTransitions;
use Tests\Fixtures\HealthPolicy;
use Tests\Fixtures\ModuleSources;
use Tests\Fixtures\TransitionPolicy;

beforeEach(function (): void {
    $this->sources = new ModuleSources;
    $this->registry = new ModuleRegistry(DB::connection(), new ModuleDiscovery($this->sources->root));
    $loader = new ModuleAutoloader($this->sources->root, $this->registry, true);
    $this->lifecycle = new LifecyclePolicies($this->sources->root, app(), $loader, true);
    $this->health = new ModuleHealth(DB::connection(), $this->registry, new HealthPolicies($this->sources->root, app(), $loader, true), new HealthDeadline);
    $this->availability = new ModuleAvailability($this->registry, $this->health, $this->lifecycle, '0.4.0');
    $this->maintenance = new MaintenanceState(app(), DB::connection(), $this->registry, $this->availability);
    $this->ids = [];
    app()->instance(ModuleHealth::class, $this->health);
    HealthPolicy::$definitions = HealthPolicy::$checks = HealthPolicy::$calls = [];
    $this->add = function (string $mode = 'on_use', string $severity = 'critical', array $required = [], string $kind = 'optional', bool $health = true): string {
        $code = 'health'.bin2hex(random_bytes(6));
        $id = 'app.'.$code;
        $definition = $this->sources->add('app', $code, $kind);
        $definition['dependencies']->required = (object) $required;
        $prefix = 'App\\Modules\\'.ucfirst($code).'\\Definitions';
        $path = $this->sources->root.'/app/modules/'.$code.'/backend/Definitions/';
        file_put_contents($path.'WorkPolicy.php', '<?php namespace '.$prefix.'; final class WorkPolicy extends \\Tests\\Fixtures\\TransitionPolicy { public const ID = '.var_export($id, true).'; }');
        if ($health) {
            $definition['declarations']->health = [$prefix.'\\Checks'];
            file_put_contents($path.'Checks.php', '<?php namespace '.$prefix.'; final class Checks extends \\Tests\\Fixtures\\HealthPolicy { public const ID = '.var_export($id, true).'; }');
        }
        $this->sources->write('app', $code, $definition);
        HealthPolicy::$definitions[$id] = ['mode' => $mode, 'severity' => $severity];
        $this->ids[] = $id;

        return $id;
    };
    $this->sync = function (): void {
        $this->registry->synchronize('app');
        DB::table('shared.modules')->whereIn('module_id', $this->ids)->update(['enabled' => true]);
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    };
});

afterEach(function (): void {
    DB::table('shared.modules')->whereIn('module_id', $this->ids)->delete();
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    $this->sources->remove();
    $this->travelBack();
});

it('runs every on-use and hybrid operation despite fresh observations but scheduled uses cached checks', function (string $mode, int $expected): void {
    $id = ($this->add)($mode);
    ($this->sync)();
    if ($mode !== 'on_use') {
        $this->health->recover();
    }
    foreach (range(1, 3) as $attempt) {
        expect($this->availability->statuses($id, 'read')[$id]->available)->toBeTrue();
    }
    expect(HealthPolicy::$calls[$id])->toBe($expected);
    expect($this->registry->snapshot()->modules[$id]->enabled)->toBeTrue();
})->with([['on_use', 3], ['hybrid', 4], ['scheduled', 1]]);

it('honors applicability and fails closed before the first scheduled critical observation', function (): void {
    $id = ($this->add)('scheduled');
    HealthPolicy::$definitions[$id]['operations'] = ['write'];
    ($this->sync)();
    expect($this->availability->statuses($id, 'read')[$id]->available)->toBeTrue();
    $status = $this->availability->statuses($id, 'write')[$id];
    expect($status->available)->toBeFalse()->and($status->ownHealth)->toBe('unknown');
    $this->health->recover();
    expect($this->availability->statuses($id, 'write')[$id]->available)->toBeTrue();
});

it('keeps refused ingress from evaluating a fenced modules healthy required dependencies', function (): void {
    $dependency = ($this->add)();
    $consumer = ($this->add)(required: [$dependency => '^0.1.0']);
    ($this->sync)();
    TransitionPolicy::$prepare[$consumer] = fn () => new Preparation('waiting');
    $transitions = new ModuleTransitions(DB::connection(), $this->registry, $this->lifecycle, app(Encrypter::class), '0.4.0', $this->health);
    $pending = null;
    try {
        $pending = $transitions->apply($transitions->preview('disable', $consumer));
        expect($pending->status)->toBe('pending');
        $before = $this->registry->snapshot();
        $calls = HealthPolicy::$calls;
        $status = $this->availability->statuses($consumer, 'read')[$consumer];
        expect($status->available)->toBeFalse()
            ->and(array_column($status->reasons, 'code'))->toContain('transition_fenced')
            ->and(HealthPolicy::$calls)->toBe($calls)
            ->and($this->registry->snapshot()->generation)->toBe($before->generation);
        unset(TransitionPolicy::$prepare[$consumer]);
        expect($transitions->resume($pending->operationId)->status)->toBe('succeeded')
            ->and($this->registry->snapshot()->modules[$dependency]->enabled)->toBeTrue();
    } finally {
        unset(TransitionPolicy::$prepare[$consumer]);
        if ($pending !== null) {
            DB::table('shared.module_transitions')->where('public_id', $pending->operationId)->delete();
        }
    }
});

it('keeps warning failure and expiry nonblocking while stale critical becomes unknown then recovers automatically', function (string $severity): void {
    $id = ($this->add)('scheduled', $severity);
    ($this->sync)();
    HealthPolicy::$checks[$id] = fn (): bool => false;
    $this->health->recover();
    $status = $this->availability->statuses($id)[$id];
    expect($status->ownHealth)->toBe($severity === 'critical' ? 'unhealthy' : 'degraded')->and($status->available)->toBe($severity !== 'critical');
    $this->travel(21)->seconds();
    $stale = $this->availability->statuses($id)[$id];
    expect($stale->ownHealth)->toBe($severity === 'critical' ? 'unknown' : 'degraded')->and($stale->available)->toBe($severity !== 'critical');
    HealthPolicy::$checks[$id] = fn (): bool => true;
    $this->health->recover();
    expect($this->availability->statuses($id)[$id]->available)->toBeTrue()->and($this->availability->statuses($id)[$id]->ownHealth)->toBe('healthy');
    expect($this->registry->snapshot()->modules[$id]->enabled)->toBeTrue();
})->with(['critical', 'warning']);

it('updates validated policy in the database with revision fencing and no deployment', function (): void {
    $id = ($this->add)();
    ($this->sync)();
    $snapshot = $this->registry->snapshot();
    $this->health->configure($id, ['mode' => 'scheduled', 'interval' => 2, 'expiry' => 4], $snapshot->generation);
    expect(fn () => $this->health->configure($id, [], $snapshot->generation))->toThrow(RuntimeException::class);
    expect(fn () => $this->health->configure($id, ['class' => 'unsafe'], $this->registry->snapshot()->generation))->toThrow(RuntimeException::class);
    expect($this->availability->statuses($id, 'read')[$id]->available)->toBeFalse();
    $this->health->recover();
    expect($this->availability->statuses($id, 'read')[$id]->available)->toBeTrue();
    $this->health->recover();
    expect(HealthPolicy::$calls[$id])->toBe(1);
    $this->travel(3)->seconds();
    $this->health->recover();
    expect(HealthPolicy::$calls[$id])->toBe(2);
});

it('evaluates a required diamond once and propagates bounded root paths without optional blocking', function (): void {
    $base = ($this->add)();
    $left = ($this->add)('on_use', 'critical', [$base => '^0.1.0']);
    $right = ($this->add)('on_use', 'critical', [$base => '^0.1.0']);
    $root = ($this->add)('on_use', 'critical', [$left => '^0.1.0', $right => '^0.1.0']);
    $independent = ($this->add)();
    ($this->sync)();
    HealthPolicy::$checks[$base] = fn (): bool => false;
    $status = $this->availability->statuses([$root, $independent], 'read');
    expect($status[$root]->available)->toBeFalse()->and($status[$independent]->available)->toBeTrue()->and(HealthPolicy::$calls[$base])->toBe(1);
    expect(array_map(fn ($reason) => $reason->path, $status[$root]->reasons))->toContain([$root, $left, $base, 'connection'], [$root, $right, $base, 'connection']);
    HealthPolicy::$checks[$base] = fn (): bool => true;
    expect($this->availability->statuses($root, 'read')[$root]->available)->toBeTrue();
});

it('rejects overlapping checks across real database connections without using fresh on-use cache', function (): void {
    $id = ($this->add)();
    ($this->sync)();
    expect($this->availability->statuses($id, 'read')[$id]->available)->toBeTrue();
    $other = new PDO('pgsql:host='.config('database.connections.pgsql.host').';port='.config('database.connections.pgsql.port').';dbname='.config('database.connections.pgsql.database'), config('database.connections.pgsql.username'), config('database.connections.pgsql.password'));
    $key = (int) hexdec(substr(hash('sha256', $id), 0, 7));
    $statement = $other->prepare('SELECT pg_advisory_lock(73006, ?)');
    $statement->execute([$key]);
    try {
        $status = $this->availability->statuses($id, 'read')[$id];
        expect($status->available)->toBeFalse()->and($status->reasons[0]->code)->toBe('health_busy')->and(HealthPolicy::$calls[$id])->toBe(1);
    } finally {
        $statement = $other->prepare('SELECT pg_advisory_unlock(73006, ?)');
        $statement->execute([$key]);
    }
    expect($this->availability->statuses($id, 'read')[$id]->available)->toBeTrue();
});

it('rejects stale results and never discloses private checker exceptions', function (): void {
    $id = ($this->add)();
    ($this->sync)();
    HealthPolicy::$checks[$id] = function (): never {
        throw new RuntimeException('private-password');
    };
    $status = $this->availability->statuses($id, 'read')[$id];
    expect($status->available)->toBeFalse()->and(json_encode($status))->not->toContain('private-password');
    HealthPolicy::$checks[$id] = function () use ($id): bool {
        $this->health->configure($id, ['mode' => 'scheduled'], $this->registry->snapshot()->generation);

        return true;
    };
    expect($this->availability->statuses($id, 'read')[$id]->available)->toBeFalse();
    expect($this->registry->snapshot()->modules[$id]->healthObservations)->toBe([]);
});

it('bounds hanging checks with a real deadline and restores process signal state', function (): void {
    $id = ($this->add)();
    ($this->sync)();
    HealthPolicy::$checks[$id] = function (): bool {
        sleep(10);

        return true;
    };
    $handler = pcntl_signal_get_handler(SIGALRM);
    $started = microtime(true);
    expect($this->availability->statuses($id, 'read')[$id]->available)->toBeFalse();
    expect(microtime(true) - $started)->toBeLessThan(3)->and(pcntl_signal_get_handler(SIGALRM))->toBe($handler);
    HealthPolicy::$checks[$id] = fn (): bool => true;
    expect($this->availability->statuses($id, 'read')[$id]->available)->toBeTrue();
});

it('composes manual update and missing Core independently while liveness and recovery remain available', function (): void {
    $id = ($this->add)('scheduled', 'critical', [], 'core');
    ($this->sync)();
    app()->instance(MaintenanceState::class, $this->maintenance);
    expect(array_keys($this->maintenance->reasons()))->toContain('core:'.$id);
    $this->getJson('/api/health')->assertOk();
    $this->getJson('/api/ready')->assertStatus(503)->assertExactJson(['status' => 'unavailable']);
    $this->getJson('/api/identity')->assertStatus(503);
    expect(Artisan::call('runa:modules:health'))->toBe(0);
    $this->health->recover();
    expect($this->maintenance->active())->toBeFalse();
    expect(Artisan::call('down'))->toBe(0);
    $manual = file_get_contents(storage_path('framework/down'));
    $directory = storage_path('framework/maintenance');
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
    file_put_contents($directory.'/enabled', "runa-update:fixture\n");
    try {
        DB::table('shared.modules')->where('module_id', $id)->update(['source_present' => false]);
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
        expect(array_keys($this->maintenance->reasons()))->toContain('manual', 'update', 'core:'.$id);
        $this->getJson('/api/health')->assertOk();
        unlink($directory.'/enabled');
        expect(array_keys($this->maintenance->reasons()))->not->toContain('update');
        expect(array_keys($this->maintenance->reasons()))->toContain('manual', 'core:'.$id);
        expect(file_get_contents(storage_path('framework/down')))->toBe($manual);
        expect(Artisan::call('up'))->toBe(0);
        expect(array_keys($this->maintenance->reasons()))->toBe(['core:'.$id]);
        expect(app()->isDownForMaintenance())->toBeTrue();
        $this->registry->synchronize('app');
        $this->health->recover();
        expect($this->maintenance->active())->toBeFalse();
    } finally {
        @unlink($directory.'/enabled');
        Artisan::call('up');
    }
});

it('treats no declared checks as healthy and preserves disabled Optional distinction', function (): void {
    $id = ($this->add)('scheduled', 'critical', [], 'optional', false);
    ($this->sync)();
    expect($this->availability->statuses($id)[$id]->available)->toBeTrue()->and($this->availability->statuses($id)[$id]->ownHealth)->toBe('healthy');
    DB::table('shared.modules')->where('module_id', $id)->update(['enabled' => false]);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    $status = $this->availability->statuses($id)[$id];
    expect($status->enabled)->toBeFalse()->and($status->available)->toBeFalse()->and($status->reasons[0]->code)->toBe('disabled')->and($this->maintenance->active())->toBeFalse();
});

it('does not traverse optional dependencies or an unrelated invalid graph', function (): void {
    $optional = ($this->add)();
    $root = ($this->add)();
    $code = substr($root, 4);
    $manifest = json_decode(file_get_contents($this->sources->root.'/app/modules/'.$code.'/module.json'));
    $manifest->dependencies->optional = (object) [$optional => '^0.1.0'];
    file_put_contents($this->sources->root.'/app/modules/'.$code.'/module.json', json_encode($manifest));
    ($this->sync)();
    HealthPolicy::$checks[$optional] = fn (): bool => false;
    $statuses = $this->availability->statuses($root, 'read');
    expect(array_keys($statuses))->toBe([$root])->and($statuses[$root]->available)->toBeTrue()->and(HealthPolicy::$calls[$optional] ?? 0)->toBe(0);
});

it('refuses reentrant on-use checks and preserves a surrounding process deadline', function (): void {
    $id = ($this->add)();
    ($this->sync)();
    HealthPolicy::$checks[$id] = function () use ($id): bool {
        $nested = $this->availability->statuses($id, 'read')[$id];
        expect($nested->available)->toBeFalse()->and($nested->reasons[0]->code)->toBe('health_busy');

        return true;
    };
    expect($this->availability->statuses($id, 'read')[$id]->available)->toBeTrue()->and(HealthPolicy::$calls[$id])->toBe(1);
    pcntl_alarm(20);
    try {
        expect($this->availability->statuses($id, 'read')[$id]->available)->toBeFalse();
        expect(pcntl_alarm(0))->toBeGreaterThan(0);
    } finally {
        pcntl_alarm(0);
    }
});

it('rejects enable previews until explicit diagnosis succeeds without changing disabled flags', function (): void {
    $id = ($this->add)();
    $this->registry->synchronize('app');
    $transitions = new ModuleTransitions(DB::connection(), $this->registry, $this->lifecycle, app(Encrypter::class), '0.4.0', $this->health);
    expect($transitions->preview('enable', $id)->blockers)->toBe([$id => 'health_unavailable']);
    expect($this->health->diagnose($id)['state'])->toBe('healthy')->and($this->registry->snapshot()->modules[$id]->enabled)->toBeFalse();
    expect($transitions->preview('enable', $id)->ready())->toBeTrue();
    $this->travel(21)->seconds();
    expect($transitions->preview('enable', $id)->ready())->toBeFalse();
});

it('rejects invalid persisted policy and source drift without leaking raw diagnostics', function (): void {
    $id = ($this->add)();
    ($this->sync)();
    DB::table('shared.modules')->where('module_id', $id)->update(['health_overrides' => '{"unknown_secret":"private"}']);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    $status = $this->availability->statuses($id, 'read')[$id];
    expect($status->ownHealth)->toBe('unknown')->and($status->available)->toBeFalse()->and(json_encode($status))->not->toContain('private');
    $file = $this->sources->root.'/app/modules/'.substr($id, 4).'/module.json';
    file_put_contents($file, file_get_contents($file)."\n");
    expect($this->availability->statuses($id, 'read')[$id]->available)->toBeFalse();
});

it('invalidates previously evaluated dependents when another module changes health mid-evaluation', function (): void {
    $base = ($this->add)('scheduled');
    $second = ($this->add)();
    ($this->sync)();
    $this->health->recover();
    HealthPolicy::$checks[$base] = fn (): bool => false;
    HealthPolicy::$checks[$second] = function () use ($base): bool {
        $this->health->diagnose($base);

        return true;
    };
    $statuses = $this->availability->statuses([$base, $second], 'read');
    expect($statuses[$base]->available)->toBeFalse()->and($statuses[$second]->available)->toBeFalse()->and($statuses[$base]->reasons[0]->code)->toBe('availability_changed');
});

it('bounds required cycles without poisoning disconnected availability', function (): void {
    $left = ($this->add)();
    $right = ($this->add)('on_use', 'critical', [$left => '^0.1.0']);
    $code = substr($left, 4);
    $file = $this->sources->root.'/app/modules/'.$code.'/module.json';
    $manifest = json_decode(file_get_contents($file));
    $manifest->dependencies->required = (object) [$right => '^0.1.0'];
    file_put_contents($file, json_encode($manifest));
    $independent = ($this->add)();
    ($this->sync)();
    $statuses = $this->availability->statuses([$left, $independent], 'read');
    expect($statuses[$left]->available)->toBeFalse()->and($statuses[$independent]->available)->toBeTrue()->and(count($statuses[$left]->reasons))->toBeLessThan(129);
});
