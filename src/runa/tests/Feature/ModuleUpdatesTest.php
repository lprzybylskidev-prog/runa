<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Runa\Contracts\Modules\PreparationContext;
use Runa\Contracts\Modules\PreparationParticipant;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\Updates\ModuleUpdates;
use Symfony\Component\Process\Process;
use Tests\Fixtures\ModuleSources;
use Tests\Fixtures\UpgradeCalls;

beforeEach(function (): void {
    $this->sources = new ModuleSources;
    $this->registry = new ModuleRegistry(DB::connection(), new ModuleDiscovery($this->sources->root));
    $this->updates = new ModuleUpdates(DB::connection(), $this->registry, $this->sources->root, '0.4.0');
    $this->codes = [];
    $this->operation = str_repeat('a', 40);
    $this->progress = [];
    $this->recordProgress = function (string $module, string $step): void {
        $this->progress[] = [$module, $step];
    };
    $this->add = function (string $owner = 'runa', string $version = '0.3.0', array $steps = []): string {
        $code = 'upgrade_'.chr(random_int(97, 122)).bin2hex(random_bytes(5));
        $this->codes[$owner.'.'.$code] = $owner.'_'.$code;
        $definition = $this->sources->add($owner, $code, 'optional', $version);
        $prefix = ($owner === 'runa' ? 'Runa' : 'App').'\\Modules\\'.str_replace(' ', '', ucwords(str_replace('_', ' ', $code)));
        $directory = $this->sources->root.'/'.$owner.'/modules/'.$code;
        mkdir($directory.'/database/migrations', 0775, true);
        $schema = $owner.'_'.$code;
        file_put_contents($directory.'/database/migrations/2026_10_22_000001_'.$schema.'_records.php', '<?php return new class extends \\Illuminate\\Database\\Migrations\\Migration { public function up(): void { \\Illuminate\\Support\\Facades\\DB::statement("CREATE TABLE '.$schema.'.records (value text NOT NULL UNIQUE)"); } };');
        if ($steps !== []) {
            $definition['declarations']->upgrades = [$prefix.'\\Definitions\\Upgrades'];
            $expressions = [];
            foreach ($steps as $index => $step) {
                [$from, $to, $transactional, $idempotent] = [...$step, true, false];
                $key = 'step_'.chr(97 + $index);
                $expressions[] = 'new \\Runa\\Contracts\\Modules\\UpgradeDefinition('.var_export($key, true).','.var_export($from, true).','.var_export($to, true).', '.var_export($prefix.'\\Handler'.chr(65 + $index), true).','.var_export($transactional, true).','.var_export($idempotent, true).($transactional ? '' : ",'reconcile_step'").')';
                $class = 'Handler'.chr(65 + $index);
                file_put_contents($directory.'/backend/'.$class.'.php', '<?php namespace '.$prefix.'; final class '.$class.' implements \\Runa\\Contracts\\Modules\\UpgradeHandler { public function run(\\Runa\\Contracts\\Modules\\UpgradeContext $context): void { $context->connection->table($context->schema->module->table("records"))->insertOrIgnore(["value" => $context->step]); \\Tests\\Fixtures\\UpgradeCalls::run($context->module.":".$context->step); } }');
            }
            file_put_contents($directory.'/backend/Definitions/Upgrades.php', '<?php namespace '.$prefix.'\\Definitions; final class Upgrades implements \\Runa\\Contracts\\Modules\\UpgradeProvider { public function upgrades(): array { return ['.implode(',', $expressions).']; } public function preflight(?string $installed, string $target): ?string { return null; } }');
        }
        $this->sources->write($owner, $code, $definition);

        return $owner.'.'.$code;
    };
    UpgradeCalls::$calls = [];
    UpgradeCalls::$fail = null;
});

afterEach(function (): void {
    DB::table('shared.module_registry_state')->where('id', 1)->update(['update_operation' => null, 'update_owner' => null, 'update_fingerprint' => null]);
    foreach ($this->codes as $id => $schema) {
        $internal = DB::table('shared.modules')->where('module_id', $id)->value('id');
        DB::table('shared.module_update_steps')->where('module_id', $internal)->delete();
        DB::table('shared.modules')->where('module_id', $id)->delete();
        // Only the exact randomized schemas created by this fixture, with no durable data.
        DB::statement('DROP TABLE IF EXISTS '.$schema.'.records RESTRICT');
        DB::statement('DROP SCHEMA IF EXISTS '.$schema.' RESTRICT');
    }
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    $this->sources->remove();
});

it('plans new disabled modules without synchronization migration execution or writes', function (): void {
    $id = ($this->add)();
    $before = $this->registry->snapshot();
    $plan = $this->updates->plan('runa');
    expect($plan)->toHaveCount(1)->and($plan[0]->source->manifest->id)->toBe($id)
        ->and($plan[0]->migrations)->toHaveCount(1)->and($plan[0]->installed)->toBeNull()
        ->and($this->registry->snapshot())->toEqual($before);
});

it('migrates disabled supplied modules and never invokes application installers or changes flags', function (): void {
    $id = ($this->add)();
    $app = ($this->add)('app');
    $this->registry->synchronize('runa');
    $before = $this->registry->snapshot()->modules[$id];
    $this->updates->apply('runa', $this->operation, $this->recordProgress);
    $after = $this->registry->snapshot()->modules[$id];
    expect($before->enabled)->toBeFalse()->and($after->enabled)->toBeFalse()->and($after->installedVersion)->toBe('0.3.0')
        ->and($this->registry->snapshot()->modules)->not->toHaveKey($app)
        ->and(DB::getSchemaBuilder()->hasTable($this->codes[$id].'.records'))->toBeTrue()
        ->and(DB::getSchemaBuilder()->hasTable($this->codes[$app].'.records'))->toBeFalse();
    $this->updates->apply('runa', $this->operation, $this->recordProgress);
    expect($this->progress)->toHaveCount(1)
        ->and($this->updates->plan('runa')[0]->toArray()['pending_migrations'])->toBe([])
        ->and($this->updates->plan('runa')[0]->toArray()['completed_steps'])->toHaveCount(1);
    // The same reusable contract is explicitly selected by the customer's deployment.
    $this->updates->apply('app', $this->operation, $this->recordProgress);
    expect($this->registry->snapshot()->modules[$app]->installedVersion)->toBe('0.3.0');
});

it('applies skipped-version chains and retains completed transactional steps across failure and resume', function (): void {
    $id = ($this->add)('runa', '0.3.0', [['0.1.0', '0.2.0', true, false], ['0.2.0', '0.3.0', true, false]]);
    $this->registry->synchronize('runa');
    DB::table('shared.modules')->where('module_id', $id)->update(['installed_version' => '0.1.0']);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    UpgradeCalls::$fail = $id.':step_b';
    expect(fn () => $this->updates->apply('runa', $this->operation, $this->recordProgress))->toThrow(RuntimeException::class);
    expect(DB::table($this->codes[$id].'.records')->pluck('value')->all())->toBe(['step_a'])
        ->and($this->registry->snapshot()->modules[$id]->installedVersion)->toBe('0.1.0')
        ->and($this->registry->snapshot()->fenced)->toContain($id);
    expect(fn () => $this->registry->synchronize('runa'))->toThrow(RuntimeException::class);
    UpgradeCalls::$fail = null;
    $this->updates->apply('runa', $this->operation, $this->recordProgress);
    expect(DB::table($this->codes[$id].'.records')->pluck('value')->all())->toBe(['step_a', 'step_b'])
        ->and(UpgradeCalls::$calls)->toBe([$id.':step_a', $id.':step_b', $id.':step_b'])
        ->and($this->registry->snapshot()->modules[$id]->installedVersion)->toBe('0.3.0')
        ->and($this->registry->snapshot()->fenced)->toBe([]);
});

it('resumes committed module steps after an independent updater process exits during transactional work', function (): void {
    $id = ($this->add)('runa', '0.3.0', [['0.1.0', '0.2.0', true, false], ['0.2.0', '0.3.0', true, false]]);
    $this->registry->synchronize('runa');
    DB::table('shared.modules')->where('module_id', $id)->update(['installed_version' => '0.1.0']);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    $script = <<<'PHP'
require 'runa/tests/bootstrap.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app->make(Runa\Support\TestDatabaseGuard::class)->assertSafe();
Tests\Fixtures\UpgradeCalls::$interrupt = $argv[2].':step_b';
$connection = Illuminate\Support\Facades\DB::connection();
$registry = new Runa\Support\Modules\ModuleRegistry($connection, new Runa\Support\Modules\ModuleDiscovery($argv[1]));
$updates = new Runa\Support\Modules\Updates\ModuleUpdates($connection, $registry, $argv[1], '0.4.0');
$updates->apply('runa', $argv[3], static function (): void {});
PHP;
    $process = new Process([PHP_BINARY, '-r', $script, $this->sources->root, $id, $this->operation], base_path(), [
        'APP_ENV' => 'testing', 'DB_DATABASE' => DB::connection()->getDatabaseName(), 'LARAVEL_STORAGE_PATH' => storage_path(),
    ]);
    $process->setTimeout(30);
    $process->run();
    expect($process->getExitCode())->toBe(23, $process->getErrorOutput())
        ->and(DB::table($this->codes[$id].'.records')->pluck('value')->all())->toBe(['step_a'])
        ->and($this->registry->snapshot()->modules[$id]->installedVersion)->toBe('0.1.0')
        ->and($this->registry->snapshot()->fenced)->toContain($id);
    $this->updates->apply('runa', $this->operation, $this->recordProgress);
    expect(UpgradeCalls::$calls)->toBe([$id.':step_b'])
        ->and(DB::table($this->codes[$id].'.records')->pluck('value')->all())->toBe(['step_a', 'step_b'])
        ->and($this->registry->snapshot()->modules[$id]->installedVersion)->toBe('0.3.0')
        ->and($this->registry->snapshot()->fenced)->toBe([]);
});

it('refuses missing paths downgrades and incompatible required or reverse application contracts before writes', function (): void {
    $id = ($this->add)('runa', '0.3.0');
    $this->registry->synchronize('runa');
    foreach (['0.1.0', '0.4.0'] as $installed) {
        DB::table('shared.modules')->where('module_id', $id)->update(['installed_version' => $installed]);
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
        expect(fn () => $this->updates->plan('runa'))->toThrow(RuntimeException::class);
    }
    DB::table('shared.modules')->where('module_id', $id)->update(['installed_version' => null]);
    $app = ($this->add)('app');
    [$owner, $code] = explode('.', $app);
    $path = $this->sources->root.'/'.$owner.'/modules/'.$code.'/module.json';
    $definition = json_decode(file_get_contents($path));
    $definition->dependencies->required = (object) [$id => '^9.0.0'];
    file_put_contents($path, json_encode($definition));
    $this->registry->synchronize('app');
    expect(fn () => $this->updates->apply('runa', $this->operation, $this->recordProgress))->toThrow(RuntimeException::class)
        ->and($this->progress)->toBe([])->and(DB::table('shared.module_registry_state')->value('update_operation'))->toBeNull();
});

it('stops on the first failed module and keeps unrelated later module data untouched', function (): void {
    $a = ($this->add)('runa', '0.3.0', [[null, '0.3.0', true, false]]);
    $b = ($this->add)();
    // Required dependency fixes order independently of randomized names.
    [$owner, $code] = explode('.', $b);
    $path = $this->sources->root.'/'.$owner.'/modules/'.$code.'/module.json';
    $definition = json_decode(file_get_contents($path));
    $definition->dependencies->required = (object) [$a => '^0.3.0'];
    file_put_contents($path, json_encode($definition));
    UpgradeCalls::$fail = $a.':step_a';
    expect(fn () => $this->updates->apply('runa', $this->operation, $this->recordProgress))->toThrow(RuntimeException::class);
    expect(DB::getSchemaBuilder()->hasTable($this->codes[$b].'.records'))->toBeFalse()
        ->and($this->registry->snapshot()->modules[$b]->installedVersion)->toBeNull()
        ->and(DB::table('shared.module_registry_state')->value('update_operation'))->toBe($this->operation);
});

it('never blindly replays ambiguous nontransactional work and allows declared idempotent recovery', function (bool $idempotent): void {
    $id = ($this->add)('runa', '0.3.0', [[null, '0.3.0', false, $idempotent]]);
    UpgradeCalls::$fail = $id.':step_a';
    expect(fn () => $this->updates->apply('runa', $this->operation, $this->recordProgress))->toThrow(RuntimeException::class);
    UpgradeCalls::$fail = null;
    if ($idempotent) {
        $this->updates->apply('runa', $this->operation, $this->recordProgress);
        expect($this->registry->snapshot()->modules[$id]->installedVersion)->toBe('0.3.0');
    } else {
        expect(fn () => $this->updates->apply('runa', $this->operation, $this->recordProgress))->toThrow(RuntimeException::class, 'replay refused');
        expect(UpgradeCalls::$calls)->toHaveCount(1);
    }
})->with([false, true]);

it('blocks changed applied migrations without executing them or discarding evidence', function (): void {
    $id = ($this->add)();
    $this->updates->apply('runa', $this->operation, $this->recordProgress);
    [$owner, $code] = explode('.', $id);
    $path = glob($this->sources->root.'/'.$owner.'/modules/'.$code.'/database/migrations/*.php')[0];
    file_put_contents($path, "\n", FILE_APPEND);
    expect(fn () => $this->updates->plan('runa'))->toThrow(RuntimeException::class, 'changed or missing');
});

it('runs the target planner in a fresh PHP process without application boot or registry writes', function (): void {
    $id = ($this->add)('runa', '0.3.0', [[null, '0.3.0', true, false]]);
    $before = $this->registry->snapshot();
    // The standalone planner reads target framework code plus installed dependencies, not the running app.
    foreach (['Contracts', 'Support'] as $directory) {
        File::copyDirectory(base_path('runa/'.$directory), $this->sources->root.'/runa/'.$directory);
    }
    $process = new Process([PHP_BINARY, dirname(base_path()).'/distribution/module-plan.php', base_path(), $this->sources->root]);
    $process->setInput(json_encode(['platform' => '0.4.0', 'modules' => [], 'module_steps' => []]));
    $process->mustRun();
    $plan = json_decode($process->getOutput(), true);
    expect($plan[0]['module'])->toBe($id)->and($plan[0]['upgrades'][0]['key'])->toBe('step_a')
        ->and($this->registry->snapshot())->toEqual($before)->and(UpgradeCalls::$calls)->toBe([]);
});

it('retains the update reservation for the same owner target and source fingerprint', function (): void {
    $id = ($this->add)('runa', '0.3.0', [[null, '0.3.0', true, false]]);
    UpgradeCalls::$fail = $id.':step_a';
    expect(fn () => $this->updates->apply('runa', $this->operation, $this->recordProgress))->toThrow(RuntimeException::class);
    expect(fn () => $this->updates->apply('app', $this->operation, $this->recordProgress))->toThrow(RuntimeException::class, 'existing module operation');
    expect(fn () => $this->updates->apply('runa', str_repeat('b', 40), $this->recordProgress))->toThrow(RuntimeException::class, 'existing module operation');
    [$owner, $code] = explode('.', $id);
    file_put_contents($this->sources->root.'/'.$owner.'/modules/'.$code.'/backend/HandlerA.php', "\n", FILE_APPEND);
    expect(fn () => $this->updates->apply('runa', $this->operation, $this->recordProgress))->toThrow(RuntimeException::class);
    expect($this->registry->snapshot()->fenced)->toContain($id);
});

it('rejects missing prerequisites and module-owned preflight refusal before registration', function (): void {
    $id = ($this->add)('runa', '0.3.0', [[null, '0.3.0', true, false]]);
    [$owner, $code] = explode('.', $id);
    $path = $this->sources->root.'/'.$owner.'/modules/'.$code.'/backend/Definitions/Upgrades.php';
    // A fresh process is used so changed trusted PHP source cannot hide behind PHP's class cache.
    foreach (['Contracts', 'Support'] as $directory) {
        File::copyDirectory(base_path('runa/'.$directory), $this->sources->root.'/runa/'.$directory);
    }
    $original = file_get_contents($path);
    foreach ([str_replace('return null;', 'return "infrastructure_unready";', $original), str_replace('true,false)', "true,false,'transaction_rollback',['missing_step'])", $original)] as $source) {
        file_put_contents($path, $source);
        $process = new Process([PHP_BINARY, dirname(base_path()).'/distribution/module-plan.php', base_path(), $this->sources->root]);
        $process->setInput(json_encode(['platform' => '0.4.0', 'modules' => [], 'module_steps' => []]));
        $process->run();
        expect($process->isSuccessful())->toBeFalse();
    }
    expect($this->registry->snapshot()->modules)->not->toHaveKey($id);
});

it('enforces a PostgreSQL read-only snapshot around runtime preflight providers', function (): void {
    $id = ($this->add)('runa', '0.3.0', [[null, '0.3.0', true, false]]);
    [$owner, $code] = explode('.', $id);
    $path = $this->sources->root.'/'.$owner.'/modules/'.$code.'/backend/Definitions/Upgrades.php';
    file_put_contents($path, str_replace('return null;', '\\Illuminate\\Support\\Facades\\DB::table("shared.module_registry_state")->where("id", 1)->increment("generation"); return null;', file_get_contents($path)));
    $before = $this->registry->snapshot();
    expect(fn () => $this->updates->plan('runa'))->toThrow(QueryException::class)
        ->and($this->registry->snapshot())->toEqual($before);
});

it('atomically refuses reserved preparation participation and resumes the exact step', function (): void {
    $id = ($this->add)('runa', '0.3.0', [[null, '0.3.0', true, false]]);
    $participant = new class implements PreparationParticipant
    {
        public bool $fail = true;

        public array $seen = [];

        public function preparing(PreparationContext $context): void
        {
            expect($context->connection)->toBe(DB::connection());
            expect($context->connection->transactionLevel())->toBeGreaterThan(0);
            expect(DB::table('shared.module_registry_state')->where('id', 1)->value('update_operation'))->toBe($context->operation);
            expect($context->owner)->toBe('runa')->and(strlen($context->fingerprint))->toBe(64);
            $this->seen[] = $context;
            $context->connection->table(str_replace('.', '_', $context->module).'.records')->insert(['value' => 'preparation']);
            if ($this->fail) {
                throw new RuntimeException('Required preparation audit failed.');
            }
        }
    };
    $updates = new ModuleUpdates(DB::connection(), $this->registry, $this->sources->root, '0.4.0', participant: $participant);
    expect(fn () => $updates->apply('runa', $this->operation, $this->recordProgress))->toThrow(RuntimeException::class, 'Required preparation audit failed.');
    expect(DB::table($this->codes[$id].'.records')->count())->toBe(0);
    expect($this->registry->snapshot()->modules[$id]->installedVersion)->toBeNull();
    expect($this->registry->snapshot()->fenced)->toContain($id);
    $participant->fail = false;
    $updates->apply('runa', $this->operation, $this->recordProgress);
    expect(DB::table($this->codes[$id].'.records')->orderBy('value')->pluck('value')->all())->toBe(['preparation', 'step_a']);
    expect($participant->seen)->toHaveCount(2);
    $updates->apply('runa', $this->operation, $this->recordProgress);
    expect($participant->seen)->toHaveCount(2);
});
