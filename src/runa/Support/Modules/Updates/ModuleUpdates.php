<?php

namespace Runa\Support\Modules\Updates;

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Runa\Contracts\Modules\PreparationContext;
use Runa\Contracts\Modules\PreparationParticipant;
use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;
use Runa\Support\Modules\Data\ModuleSchema;
use Runa\Support\Modules\ModuleIdentity;
use Runa\Support\Modules\ModuleRegistry;
use RuntimeException;

/** Steps are database completion evidence belonging to the caller's existing operation journal. */
final readonly class ModuleUpdates
{
    public function __construct(private Connection $connection, private ModuleRegistry $registry, private string $root, private string $platform, private bool $development = false, private PreparationParticipant $participant = new NoPreparationParticipation) {}

    /** @return array<string, array{installed: ?string, version: string, registered: bool, required: array<string, string>}> */
    public function state(): array
    {
        $state = [];
        /** @var list<object{module_id: string, installed_version: ?string, source_version: string, registered: bool, source_present: bool, manifest: string}> $rows */
        $rows = $this->connection->table('shared.modules')->orderBy('module_id')->get()->all();
        foreach ($rows as $row) {
            /** @var array{dependencies: array{required: array<string, string>}} $manifest */
            $manifest = json_decode($row->manifest, true, 16, JSON_THROW_ON_ERROR);
            $state[$row->module_id] = ['installed' => $row->installed_version, 'version' => $row->source_version, 'registered' => $row->registered, 'required' => $manifest['dependencies']['required']];
        }

        return $state;
    }

    /** @return list<PlannedModule> */
    public function plan(string $owner): array
    {
        if ($this->connection->transactionLevel() !== 0) {
            throw new RuntimeException('Module planning requires its own read-only transaction.');
        }

        return $this->connection->transaction(function () use ($owner): array {
            $this->connection->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
            $plan = (new ModuleUpdatePlanner($this->root, $this->platform, $this->development))->plan($owner, $this->state());
            $history = $this->validateHistory($plan);

            return array_map(fn (PlannedModule $module): PlannedModule => $module->withHistory($history), $plan);
        });
    }

    /** @param list<PlannedModule> $plan
     * @return list<array{module: string, step: string, fingerprint: string, status: string}>
     */
    private function validateHistory(array $plan): array
    {
        /** @var list<object{module: string, step: string, fingerprint: string, status: string}> $rows */
        $rows = $this->connection->table('shared.module_update_steps as s')->join('shared.modules as m', 'm.id', '=', 's.module_id')->get(['m.module_id as module', 's.step', 's.fingerprint', 's.status'])->all();
        $history = array_map(static fn ($row): array => ['module' => $row->module, 'step' => $row->step, 'fingerprint' => $row->fingerprint, 'status' => $row->status], $rows);
        ModuleStepHistory::validate($this->root, $plan, $history);

        return $history;
    }

    /** Explicit installation/deployment invocation. The caller owns writer shutdown, maintenance and its journal/lock.
     * @param  callable(string, string): void  $progress  Records module/key in that journal before each step.
     */
    public function apply(string $owner, string $operation, callable $progress): void
    {
        if (preg_match('/\A[a-f0-9]{40}\z/D', $operation) !== 1 || $this->connection->transactionLevel() !== 0) {
            throw new RuntimeException('Module updates require an exact source operation and no ambient transaction.');
        }
        /** @var object{locked: bool} $lock */
        $lock = $this->connection->selectOne("SELECT pg_try_advisory_lock(hashtextextended('runa-module-update', 0)) AS locked");
        if (! $lock->locked) {
            throw new RuntimeException('Another module update executor is running.');
        }
        try {
            // Plan/preflight before publishing any registry or fence change.
            $plan = $this->plan($owner);
            $reservationFingerprint = hash('sha256', json_encode(array_map(fn (PlannedModule $module): array => [$module->source->manifest->id, $module->fingerprint], $plan), JSON_THROW_ON_ERROR));
            $this->connection->transaction(function () use ($operation, $owner, $reservationFingerprint): void {
                $this->connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first();
                $active = $this->connection->table('shared.module_registry_state')->where('id', 1)->value('update_operation');
                if (($active !== null && ($active !== $operation || $this->connection->table('shared.module_registry_state')->where('id', 1)->value('update_owner') !== $owner || $this->connection->table('shared.module_registry_state')->where('id', 1)->value('update_fingerprint') !== $reservationFingerprint)) || $this->connection->table('shared.module_transitions')->whereIn('status', ModuleRegistry::ACTIVE_TRANSITIONS)->exists()) {
                    throw new RuntimeException('Reconcile the existing module operation before updating.');
                }
                $this->registry->synchronize($owner, $operation);
                $this->connection->table('shared.module_registry_state')->where('id', 1)->update(['update_operation' => $operation, 'update_owner' => $owner, 'update_fingerprint' => $reservationFingerprint]);
                $this->connection->table('shared.module_registry_state')->where('id', 1)->increment('generation');
            });
            foreach ($plan as $module) {
                $id = $module->source->manifest->id;
                foreach ($module->migrations as $name => $fingerprint) {
                    $this->step($id, 'migration:'.$name, $fingerprint, $operation, true, false, $progress, function () use ($module, $name, $id): void {
                        $path = $this->root.'/'.$module->source->path.'/database/migrations/'.$name.'.php';
                        $migration = require $path;
                        if (! $migration instanceof Migration || ! is_callable([$migration, 'up']) || ! $migration->withinTransaction || ($migration->getConnection() !== null && $migration->getConnection() !== $this->connection->getName())) {
                            throw new RuntimeException('Module migrations require the registry connection and transactional PostgreSQL work.');
                        }
                        $schema = new ModuleSchema($this->connection, new ModuleIdentity($id));
                        $schema->claim();
                        $migration->up();
                        $schema->assertForeignKeys();
                    });
                }
                foreach ($module->steps as $step) {
                    $key = ModuleStepHistory::key($step);
                    $fingerprint = ModuleStepHistory::fingerprint($this->root, $module, $step);
                    $this->step($id, $key, $fingerprint, $operation, $step->transactional, $step->idempotent, $progress, function () use ($module, $step, $id, $operation, $owner, $fingerprint, $key, $reservationFingerprint): void {
                        if ($step->transactional) {
                            $state = $this->connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first();
                            if ($state === null || $state->update_operation !== $operation || $state->update_owner !== $owner || $state->update_fingerprint !== $reservationFingerprint) {
                                throw new RuntimeException('Preparation reservation changed.');
                            }
                            $record = $this->registry->snapshot()->modules[$id] ?? throw new RuntimeException('Preparation source missing.');
                            if (! $record->registered || ! $record->sourcePresent || $record->manifestHash !== hash_file('sha256', $this->root.'/'.$module->source->path.'/module.json')) {
                                throw new RuntimeException('Preparation source changed.');
                            }
                            $this->participant->preparing(new PreparationContext($this->connection, $owner, $operation, $id, $key, $fingerprint, $record->manifestHash, $reservationFingerprint));
                        }
                        $handler = (new UpgradeDefinitions($this->root))->instance($module->source, $step->handler, UpgradeHandler::class);
                        $handler->run(new UpgradeContext($operation, $id, $step->from, $step->to, $step->key, $this->connection, new ModuleSchema($this->connection, new ModuleIdentity($id))));
                    });
                }
                $this->connection->transaction(function () use ($id, $module): void {
                    $this->connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first();
                    $this->connection->table('shared.modules')->where('module_id', $id)->update(['installed_version' => $module->source->manifest->version, 'updated_at' => now()]);
                    $this->connection->table('shared.module_registry_state')->where('id', 1)->increment('generation');
                });
            }
            $this->connection->transaction(function () use ($operation): void {
                $this->connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first();
                $this->connection->table('shared.module_registry_state')->where('id', 1)->where('update_operation', $operation)->update(['update_operation' => null, 'update_owner' => null, 'update_fingerprint' => null]);
                $this->connection->table('shared.module_registry_state')->where('id', 1)->increment('generation');
            });
        } finally {
            $this->connection->select("SELECT pg_advisory_unlock(hashtextextended('runa-module-update', 0))");
        }
    }

    /** @param callable(string, string): void $progress
     * @param  callable(): void  $run
     */
    private function step(string $id, string $key, string $fingerprint, string $operation, bool $transactional, bool $idempotent, callable $progress, callable $run): void
    {
        $moduleId = $this->connection->table('shared.modules')->where('module_id', $id)->value('id');
        $query = $this->connection->table('shared.module_update_steps')->where('module_id', $moduleId)->where('step', $key);
        /** @var object{fingerprint: string, status: string}|null $existing */
        $existing = $query->first();
        if ($existing !== null) {
            if ($existing->fingerprint !== $fingerprint) {
                throw new RuntimeException('Recorded module step source changed.');
            }
            if ($existing->status === 'succeeded') {
                return;
            }
            if (! $transactional && ! $idempotent) {
                throw new RuntimeException('Ambiguous non-idempotent module step requires declared operator recovery; replay refused.');
            }
        }
        $progress($id, $key);
        $execute = function () use ($query, $moduleId, $key, $fingerprint, $operation, $run): void {
            $query->updateOrInsert(['module_id' => $moduleId, 'step' => $key], ['operation' => $operation, 'fingerprint' => $fingerprint, 'status' => 'running', 'created_at' => now(), 'updated_at' => now()]);
            $run();
            $query->update(['status' => 'succeeded', 'updated_at' => now()]);
        };
        if ($transactional) {
            // Data/schema effects and completion record roll back together, with no implicit retry.
            $this->connection->transaction($execute);
        } else {
            $query->updateOrInsert(['module_id' => $moduleId, 'step' => $key], ['operation' => $operation, 'fingerprint' => $fingerprint, 'status' => 'running', 'created_at' => now(), 'updated_at' => now()]);
            $run();
            $query->update(['status' => 'succeeded', 'updated_at' => now()]);
        }
    }
}
