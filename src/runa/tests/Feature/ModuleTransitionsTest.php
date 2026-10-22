<?php

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\AdministrativePreview;
use Runa\Contracts\Modules\LifecyclePlan;
use Runa\Contracts\Modules\Preparation;
use Runa\Support\Modules\Data\ModuleSchema;
use Runa\Support\Modules\HealthDeadline;
use Runa\Support\Modules\HealthPolicies;
use Runa\Support\Modules\LifecyclePolicies;
use Runa\Support\Modules\ModuleAutoloader;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleHealth;
use Runa\Support\Modules\ModuleIdentity;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\ModuleTransitions;
use Runa\Support\Modules\Storage\ModuleStorage;
use Symfony\Component\Process\Process;
use Tests\Fixtures\ModuleSources;
use Tests\Fixtures\TransitionPolicy;

beforeEach(function (): void {
    // Real commits are necessary to test durable ingress fences and hook transaction boundaries.
    expect(DB::table('shared.modules')->count())->toBe(0);
    expect(DB::table('shared.module_transitions')->count())->toBe(0);
    $this->sources = new ModuleSources;
    $this->registry = new ModuleRegistry(DB::connection(), new ModuleDiscovery($this->sources->root));
    $this->policies = new LifecyclePolicies($this->sources->root, app(), new ModuleAutoloader($this->sources->root, $this->registry, true), true);
    $this->health = new ModuleHealth(DB::connection(), $this->registry, new HealthPolicies($this->sources->root, app(), new ModuleAutoloader($this->sources->root, $this->registry, true), true), new HealthDeadline);
    $this->transitions = new ModuleTransitions(DB::connection(), $this->registry, $this->policies, app(Encrypter::class), '0.4.0', $this->health);
    $this->ids = [];
    $this->add = function (array $required = [], string $kind = 'optional'): string {
        $code = 'transition'.bin2hex(random_bytes(6));
        $id = 'app.'.$code;
        $definition = $this->sources->add('app', $code, $kind);
        $definition['dependencies']->required = (object) $required;
        $this->sources->write('app', $code, $definition);
        file_put_contents($this->sources->root.'/app/modules/'.$code.'/backend/Definitions/WorkPolicy.php', '<?php namespace App\\Modules\\'.ucfirst($code).'\\Definitions; final class WorkPolicy extends \\Tests\\Fixtures\\TransitionPolicy { public const ID = '.var_export($id, true).'; }');
        $this->ids[] = $id;

        return $id;
    };
    TransitionPolicy::$prepare = TransitionPolicy::$compensate = TransitionPolicy::$revisions = TransitionPolicy::$calls = [];
});

afterEach(function (): void {
    DB::table('shared.module_transitions')->whereIn('target', $this->ids)->delete();
    DB::table('shared.modules')->whereIn('module_id', $this->ids)->delete();
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    if (isset($this->retainedSchema)) {
        DB::statement('DROP TABLE app_transition_retention.marker');
        DB::statement('DROP SCHEMA app_transition_retention');
    }
    if (isset($this->ownedSchema)) {
        DB::statement('DROP TABLE '.$this->ownedSchema.'.records');
        DB::statement('DROP SCHEMA '.$this->ownedSchema);
    }
    if (isset($this->ownedStorage)) {
        File::deleteDirectory($this->ownedStorage);
        Storage::forgetDisk('application_private');
    }
    $this->sources->remove();
});

it('publishes dependency flags atomically with visible fences outside hook transactions and no historical reactivation', function (): void {
    $base = ($this->add)();
    $target = ($this->add)([$base => '^0.1.0']);
    $before = $this->registry->synchronize('app');
    foreach ([$base, $target] as $id) {
        TransitionPolicy::$prepare[$id] = function () use ($base, $target): Preparation {
            expect(DB::connection()->transactionLevel())->toBe(0);
            $observer = new ModuleRegistry(DB::connection(), new ModuleDiscovery($this->sources->root));
            $snapshot = $observer->snapshot();
            expect($snapshot->fenced)->toBe([$base, $target])->and($snapshot->modules[$base]->enabled)->toBeFalse()->and($snapshot->modules[$target]->enabled)->toBeFalse();
            expect(fn () => $observer->synchronize('app'))->toThrow(RuntimeException::class);

            return new Preparation('ready', 'opaque-private-token');
        };
    }
    $result = $this->transitions->apply($this->transitions->preview('enable', $target));
    expect($result->status)->toBe('succeeded')->and($result->generation)->toBe($before->generation + 2);
    expect($this->registry->snapshot()->fenced)->toBe([]);
    expect(DB::table('shared.modules')->whereIn('module_id', [$base, $target])->pluck('enabled')->all())->toBe([true, true]);
    $stored = DB::table('shared.module_transitions')->where('public_id', $result->operationId)->value('payload');
    expect($stored)->not->toContain('opaque-private-token');
    expect(json_encode($result))->not->toContain('opaque-private-token');
    TransitionPolicy::$prepare = [];
    expect($this->transitions->apply($this->transitions->preview('disable', $base))->status)->toBe('succeeded');
    expect($this->transitions->apply($this->transitions->preview('enable', $base))->status)->toBe('succeeded');
    expect($this->registry->snapshot()->modules[$target]->enabled)->toBeFalse();
    expect($this->transitions->resume($result->operationId))->toEqual($result);
});

it('compensates every attempted hook in reverse order without publishing any flags', function (int $failure, string $mode, string $intent): void {
    $base = ($this->add)();
    $middle = ($this->add)([$base => '^0.1.0']);
    $target = ($this->add)([$middle => '^0.1.0']);
    $this->registry->synchronize('app');
    $ids = $intent === 'enable' ? [$base, $middle, $target] : [$target, $middle, $base];
    if ($intent === 'disable') {
        DB::table('shared.modules')->whereIn('module_id', $ids)->update(['enabled' => true]);
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    }
    TransitionPolicy::$prepare[$ids[$failure]] = function () use ($mode): Preparation {
        if ($mode === 'throw') {
            throw new RuntimeException('sensitive upstream payload');
        }

        return new Preparation('refused', 'refused-token', 'work_refused');
    };
    $result = $this->transitions->apply($this->transitions->preview($intent, $intent === 'enable' ? $target : $base));
    expect($result->status)->toBe($mode === 'throw' ? 'failed' : 'refused');
    foreach ($ids as $id) {
        expect($this->registry->snapshot()->modules[$id]->enabled)->toBe($intent === 'disable');
    }
    $attempted = array_slice($ids, 0, $failure + 1);
    expect(TransitionPolicy::$calls)->toBe([...array_map(fn ($id) => 'prepare:'.$id, $attempted), ...array_map(fn ($id) => 'compensate:'.$id, array_reverse($attempted))]);
    expect($this->registry->snapshot()->fenced)->toBe([]);
    expect(json_encode($result))->not->toContain('sensitive');
})->with([[0, 'throw'], [1, 'throw'], [2, 'throw'], [0, 'refuse'], [1, 'refuse'], [2, 'refuse']])->with(['enable', 'disable']);

it('persists waiting tokens and resumes with a new service instance while rejecting competing transitions', function (): void {
    $id = ($this->add)();
    $this->registry->synchronize('app');
    TransitionPolicy::$prepare[$id] = fn () => new Preparation('waiting', 'checkpoint', 'draining');
    $preview = $this->transitions->preview('enable', $id);
    $result = $this->transitions->apply($preview);
    expect($result->status)->toBe('pending')->and($this->registry->snapshot()->modules[$id]->enabled)->toBeFalse();
    expect(fn () => $this->transitions->apply($preview))->toThrow(RuntimeException::class);
    expect(fn () => $this->registry->synchronize('app'))->toThrow(RuntimeException::class);
    TransitionPolicy::$prepare[$id] = function ($context, $token): Preparation {
        expect($token)->toBe('checkpoint');

        return new Preparation('ready');
    };
    $worker = new ModuleTransitions(DB::connection(), $this->registry, $this->policies, app(Encrypter::class), '0.4.0', $this->health);
    expect($worker->resume($result->operationId)->status)->toBe('succeeded');
    expect($this->registry->snapshot()->modules[$id]->enabled)->toBeTrue();
});

it('retains a recovery fence when compensation fails and reconciles an interrupted hook with a null token', function (): void {
    $id = ($this->add)();
    $this->registry->synchronize('app');
    TransitionPolicy::$prepare[$id] = fn () => new Preparation('waiting');
    $result = $this->transitions->apply($this->transitions->preview('enable', $id));
    // Emulate a runner dying after persisting intent but before recording a hook result.
    DB::table('shared.module_transitions')->where('public_id', $result->operationId)->update(['status' => 'preparing']);
    TransitionPolicy::$compensate[$id] = function ($context, $token): bool {
        expect($token)->toBeNull();

        return false;
    };
    $failed = $this->transitions->resume($result->operationId);
    expect($failed->status)->toBe('recovery_required')->and($this->registry->snapshot()->fenced)->toBe([$id]);
    expect($this->registry->snapshot()->modules[$id]->enabled)->toBeFalse();
    TransitionPolicy::$compensate[$id] = fn () => true;
    expect($this->transitions->resume($result->operationId)->status)->toBe('failed');
    expect($this->registry->snapshot()->fenced)->toBe([]);
});

it('rejects stale policy plans and compensates changed policy revisions before publication', function (): void {
    $id = ($this->add)();
    $this->registry->synchronize('app');
    $preview = $this->transitions->preview('enable', $id);
    TransitionPolicy::$revisions[$id] = '2';
    expect(fn () => $this->transitions->apply($preview))->toThrow(RuntimeException::class);
    $current = $this->transitions->preview('enable', $id);
    $forged = new AdministrativePreview($current->graph, [$id => new LifecyclePlan('forged')], []);
    expect(fn () => $this->transitions->apply($forged))->toThrow(RuntimeException::class);
    TransitionPolicy::$prepare[$id] = function () use ($id): Preparation {
        TransitionPolicy::$revisions[$id] = '3';

        return new Preparation('ready');
    };
    expect($this->transitions->apply($current)->status)->toBe('failed');
    expect($this->registry->snapshot()->modules[$id]->enabled)->toBeFalse();
});

it('refuses direct and cascading Core disable or unregister even with forged disabled state', function (): void {
    $dependency = ($this->add)();
    $core = ($this->add)([$dependency => '^0.1.0'], 'core');
    $this->registry->synchronize('app');
    foreach ([['disable', $core], ['disable', $dependency], ['unregister', $core]] as [$intent, $id]) {
        expect($this->transitions->preview($intent, $id)->ready())->toBeFalse();
        expect(fn () => $this->transitions->apply($this->transitions->preview($intent, $id)))->toThrow(RuntimeException::class);
    }
    expect(fn () => DB::transaction(fn () => DB::table('shared.modules')->where('module_id', $core)->update(['enabled' => false])))->toThrow(QueryException::class);
});

it('preserves missing metadata and retained files through unregister and explicit incompatible re-registration', function (): void {
    $id = ($this->add)();
    $consumer = ($this->add)([$id => '^0.1.0']);
    $this->registry->synchronize('app');
    expect($this->transitions->preview('unregister', $id)->ready())->toBeFalse();
    expect($this->transitions->apply($this->transitions->preview('unregister', $consumer))->status)->toBe('succeeded');
    $code = explode('.', $id)[1];
    DB::table('shared.modules')->where('module_id', $id)->update(['installed_version' => '0.1.0']);
    DB::statement('CREATE SCHEMA app_transition_retention');
    DB::statement('CREATE TABLE app_transition_retention.marker (value text NOT NULL)');
    $this->retainedSchema = true;
    DB::table('app_transition_retention.marker')->insert(['value' => 'preserved domain data']);
    $metadata = DB::table('shared.modules')->where('module_id', $id)->value('manifest');
    file_put_contents($this->sources->root.'/retained-data', 'business data');
    rename($this->sources->root.'/app/modules/'.$code, $this->sources->root.'/missing');
    $this->registry->synchronize('app');
    expect($this->registry->snapshot()->modules[$id]->sourcePresent)->toBeFalse();
    expect($this->transitions->apply($this->transitions->preview('unregister', $id))->status)->toBe('succeeded');
    $tombstone = $this->registry->snapshot()->modules[$id];
    expect($tombstone->registered)->toBeFalse()->and($tombstone->installedVersion)->toBe('0.1.0');
    expect(DB::table('app_transition_retention.marker')->value('value'))->toBe('preserved domain data');
    expect(DB::table('shared.modules')->where('module_id', $id)->value('manifest'))->toBe($metadata);
    rename($this->sources->root.'/missing', $this->sources->root.'/app/modules/'.$code);
    $file = $this->sources->root.'/app/modules/'.$code.'/module.json';
    $manifest = json_decode(file_get_contents($file));
    $manifest->version = '0.2.0';
    file_put_contents($file, json_encode($manifest));
    $this->registry->synchronize('app');
    expect($this->registry->snapshot()->modules[$id]->registered)->toBeFalse();
    $new = $this->registry->registerAgain($id)->modules[$id];
    expect($new->enabled)->toBeFalse()->and($new->installedVersion)->toBe('0.1.0')->and($new->publicId)->not->toBe($tombstone->publicId);
    expect($this->transitions->preview('enable', $id)->blockers)->toBe([$id => 'installed_data_incompatible']);
    expect(file_get_contents($this->sources->root.'/retained-data'))->toBe('business data');
});

it('retains owned schema and scoped disk data through enable disable unregister and restoration', function (): void {
    $id = ($this->add)();
    $this->registry->synchronize('app');
    $module = new ModuleIdentity($id);
    $schema = new ModuleSchema(DB::connection(), $module);
    $this->ownedSchema = $module->schema();
    $schema->create('records', fn (Blueprint $table) => $table->text('name'));
    DB::table($module->table('records'))->insert(['public_id' => (string) Str::uuid7(), 'name' => 'retained']);
    $this->ownedStorage = $this->sources->root.'/owned-storage';
    config(['filesystems.disks.application_private.root' => $this->ownedStorage]);
    Storage::forgetDisk('application_private');
    $disk = app(ModuleStorage::class)->disk($id);
    $disk->put('records/retained.txt', 'retained');
    foreach (['enable', 'disable', 'unregister'] as $intent) {
        expect($this->transitions->apply($this->transitions->preview($intent, $id))->status)->toBe('succeeded');
        expect(DB::table($module->table('records'))->value('name'))->toBe('retained')->and($disk->get('records/retained.txt'))->toBe('retained');
    }
    $this->registry->registerAgain($id);
    expect(DB::table($module->table('records'))->value('name'))->toBe('retained')->and($disk->get('records/retained.txt'))->toBe('retained');
});

it('serializes independent PostgreSQL runners without long transactions or nested runner entry', function (): void {
    $id = ($this->add)();
    $this->registry->synchronize('app');
    config(['database.connections.module_transition_observer' => config('database.connections.pgsql')]);
    $other = DB::connection('module_transition_observer');
    try {
        $other->selectOne('SELECT pg_advisory_lock(1381322305, 4)');
        expect(fn () => $this->transitions->apply($this->transitions->preview('enable', $id)))->toThrow(RuntimeException::class);
        $other->selectOne('SELECT pg_advisory_unlock(1381322305, 4)');
        TransitionPolicy::$prepare[$id] = function () use ($other, $id): Preparation {
            expect(DB::connection()->transactionLevel())->toBe(0);
            expect($other->selectOne('SELECT pg_try_advisory_lock(1381322305, 4) AS acquired')->acquired)->toBeFalse();
            $observer = new ModuleRegistry($other, new ModuleDiscovery($this->sources->root));
            expect($observer->snapshot()->fenced)->toBe([$id]);
            expect(fn () => $observer->synchronize('app'))->toThrow(RuntimeException::class);
            expect(fn () => $this->transitions->apply($this->transitions->preview('enable', $id)))->toThrow(RuntimeException::class);

            return new Preparation('ready');
        };
        expect($this->transitions->apply($this->transitions->preview('enable', $id))->status)->toBe('succeeded');
    } finally {
        $other->disconnect();
        DB::purge('module_transition_observer');
    }
});

it('cancels waiting preparation without publishing flags and refuses stale source and missing policies', function (): void {
    $id = ($this->add)();
    $snapshot = $this->registry->synchronize('app');
    TransitionPolicy::$prepare[$id] = fn () => new Preparation('waiting', 'cancel-me', 'work_pending');
    $waiting = $this->transitions->apply($this->transitions->preview('enable', $id));
    expect($this->transitions->cancel($waiting->operationId)->status)->toBe('failed');
    expect($this->registry->snapshot()->fenced)->toBe([])->and($this->registry->snapshot()->modules[$id]->enabled)->toBeFalse();
    $code = explode('.', $id)[1];
    $file = $this->sources->root.'/app/modules/'.$code.'/module.json';
    $manifest = json_decode(file_get_contents($file));
    $manifest->version = '0.2.0';
    file_put_contents($file, json_encode($manifest));
    expect($this->transitions->preview('enable', $id)->blockers)->toBe([$id => 'policy_unavailable']);
    rename($this->sources->root.'/app/modules/'.$code, $this->sources->root.'/missing');
    $restored = $this->registry->synchronize('app');
    expect($restored->modules[$id]->sourcePresent)->toBeFalse();
    expect($this->transitions->preview('enable', $id)->ready())->toBeFalse();
    rename($this->sources->root.'/missing', $this->sources->root.'/app/modules/'.$code);
    $restored = $this->registry->synchronize('app');
    expect($restored->modules[$id]->sourcePresent)->toBeTrue()->and($restored->modules[$id]->enabled)->toBeFalse();
    expect($restored->modules[$id]->publicId)->toBe($snapshot->modules[$id]->publicId);
});

it('rolls back every published flag on a database publication failure after ready hooks', function (): void {
    $base = ($this->add)();
    $target = ($this->add)([$base => '^0.1.0']);
    $this->registry->synchronize('app');
    // A real deferred constraint is unnecessary: reject the final row update in PostgreSQL.
    DB::statement('ALTER TABLE shared.modules ADD CONSTRAINT module_transition_test_failure CHECK (module_id <> '.DB::connection()->getPdo()->quote($target).' OR NOT enabled)');
    try {
        $result = $this->transitions->apply($this->transitions->preview('enable', $target));
        expect($result->status)->toBe('failed');
        expect($this->registry->snapshot()->modules[$base]->enabled)->toBeFalse()->and($this->registry->snapshot()->modules[$target]->enabled)->toBeFalse();
        expect(TransitionPolicy::$calls)->toBe(['prepare:'.$base, 'prepare:'.$target, 'compensate:'.$target, 'compensate:'.$base]);
    } finally {
        DB::statement('ALTER TABLE shared.modules DROP CONSTRAINT module_transition_test_failure');
    }
});

it('recovers a real worker exit between durable hook intent and preparation response', function (): void {
    $id = ($this->add)();
    $this->registry->synchronize('app');
    $worker = <<<'WORKER'
require 'runa/tests/bootstrap.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app->make(Runa\Support\TestDatabaseGuard::class)->assertSafe();
$db = Illuminate\Support\Facades\DB::connection();
$registry = new Runa\Support\Modules\ModuleRegistry($db, new Runa\Support\Modules\ModuleDiscovery($argv[1]));
$policies = new Runa\Support\Modules\LifecyclePolicies($argv[1], $app, new Runa\Support\Modules\ModuleAutoloader($argv[1], $registry, true), true);
$transitions = new Runa\Support\Modules\ModuleTransitions($db, $registry, $policies, $app->make(Illuminate\Contracts\Encryption\Encrypter::class), '0.4.0', new Runa\Support\Modules\ModuleHealth($db, $registry, new Runa\Support\Modules\HealthPolicies($argv[1], $app, new Runa\Support\Modules\ModuleAutoloader($argv[1], $registry, true), true), new Runa\Support\Modules\HealthDeadline));
Tests\Fixtures\TransitionPolicy::$prepare[$argv[2]] = static function () { exit(23); };
$transitions->apply($transitions->preview('enable', $argv[2]));
WORKER;
    $process = new Process([PHP_BINARY, '-r', $worker, $this->sources->root, $id], base_path(), ['APP_ENV' => 'testing', 'DB_DATABASE' => 'runa_test', 'LARAVEL_STORAGE_PATH' => storage_path()]);
    expect($process->run())->toBe(23, $process->getErrorOutput());
    $operation = DB::table('shared.module_transitions')->where('target', $id)->first();
    expect($operation->status)->toBe('preparing');
    expect($this->registry->snapshot()->fenced)->toBe([$id])->and($this->registry->snapshot()->modules[$id]->enabled)->toBeFalse();
    TransitionPolicy::$compensate[$id] = function ($context, $token) use ($operation): bool {
        expect($context->operationId)->toBe($operation->public_id)->and($token)->toBeNull();

        return true;
    };
    expect($this->transitions->resume($operation->public_id)->status)->toBe('failed');
    expect($this->registry->snapshot()->fenced)->toBe([]);
});
