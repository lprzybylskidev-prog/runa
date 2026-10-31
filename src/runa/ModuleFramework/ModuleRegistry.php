<?php

namespace Runa\ModuleFramework;

use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use Runa\Support\Modules\ModuleRecord;
use Runa\Support\Modules\RegistrySnapshot;
use RuntimeException;
use WeakReference;

final class ModuleRegistry
{
    public const ACTIVE_TRANSITIONS = ['preparing', 'pending', 'compensating', 'recovery_required'];

    private ?RegistrySnapshot $cached = null;

    private ?int $cachedLevel = null;

    /** @var WeakReference<\PDO>|null */
    private ?WeakReference $cachedPdo = null;

    public function __construct(private readonly Connection $connection, private readonly ModuleDiscovery $discovery)
    {
        // Connection callbacks remain active even when model/database events are suppressed.
        // Weak references do not retain discarded registry fixtures in a reused connection.
        $registry = WeakReference::create($this);
        $connection->beforeStartingTransaction(static function (Connection $connection) use ($registry): void {
            $instance = $registry->get();
            // A read-only child retains its ancestor's lock. A new root or a
            // child entered after rolling back the cached level must reload.
            if ($connection->transactionLevel() === 0 || ($instance?->cachedLevel ?? 0) > $connection->transactionLevel()) {
                $instance?->forgetSnapshot();
            }
        });
        $connection->beforeExecuting(static function (string $sql) use ($registry): void {
            // Only a single plain SELECT is read-only here. Treat CTEs, raw/multiple
            // statements and all writes conservatively, without inspecting bindings.
            if (! str_starts_with(strtolower(ltrim($sql)), 'select ') || str_contains($sql, ';')) {
                $registry->get()?->forgetSnapshot();
            }
        });
    }

    private function forgetSnapshot(): void
    {
        $this->cached = null;
        $this->cachedLevel = null;
        $this->cachedPdo = null;
    }

    /** Explicit installation/deployment operation, never called during request boot. */
    public function synchronize(string $owner, ?string $operation = null): RegistrySnapshot
    {
        $this->connection->transaction(function () use ($owner, $operation): void {
            // One existing singleton serializes discovery publication across both owners.
            $generation = $this->connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->value('generation');
            if ($generation === null) {
                throw new RuntimeException('Module registry migration is required.');
            }
            $updating = $this->connection->table('shared.module_registry_state')->where('id', 1)->first()->update_operation ?? null;
            if (($updating !== null && $updating !== $operation) || $this->connection->table('shared.module_transitions')->whereIn('status', self::ACTIVE_TRANSITIONS)->exists()) {
                throw new RuntimeException('Complete or recover the module transition before synchronizing source.');
            }
            $sources = $this->discovery->discover($owner);
            $changed = false;
            $found = [];
            foreach ($sources as $source) {
                $manifest = $source->manifest;
                $found[] = $manifest->id;
                $query = $this->connection->table('shared.modules')->where('module_id', $manifest->id);
                /** @var object{kind: string, registered: bool, source_present: bool, source_version: string, source_path: string, manifest_hash: string}|null $existing */
                $existing = $query->first();
                if ($existing !== null && $existing->kind !== $manifest->kind) {
                    throw new RuntimeException('A module kind cannot reinterpret an existing registration.');
                }
                $values = [
                    'source_present' => true,
                    'source_version' => $manifest->version,
                    'source_path' => $source->path,
                    'manifest_hash' => $source->manifestHash,
                    'manifest' => json_encode((object) $manifest->definition, JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                ];
                if ($existing === null) {
                    $this->connection->table('shared.modules')->insert([
                        ...$values, 'public_id' => (string) Str::uuid7(), 'module_id' => $manifest->id,
                        'owner' => $manifest->owner, 'code' => $manifest->code, 'kind' => $manifest->kind,
                        'registered' => true, 'enabled' => $manifest->kind === 'core', 'created_at' => now(),
                    ]);
                    $changed = true;
                } elseif ($existing->registered && (! $existing->source_present || $existing->manifest_hash !== $source->manifestHash || $existing->source_path !== $source->path || $existing->source_version !== $manifest->version)) {
                    $query->update($values);
                    $changed = true;
                }
                // Retained unregistered provenance is deliberately not reactivated by sync.
                // Its explicit restoration policy belongs to the lifecycle adapter.
            }
            $missing = $this->connection->table('shared.modules')->where('owner', $owner)->where('registered', true)->where('source_present', true)->whereNotIn('module_id', $found);
            if ($missing->update(['source_present' => false, 'updated_at' => now()]) > 0) {
                $changed = true;
            }
            if ($changed) {
                $this->connection->table('shared.module_registry_state')->where('id', 1)->increment('generation');
            }
        });

        return $this->snapshot();
    }

    /** Explicit new registration over a tombstone; preserves installed upgrade provenance. */
    public function registerAgain(string $id): RegistrySnapshot
    {
        $this->connection->transaction(function () use ($id): void {
            $this->connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first();
            if (($this->connection->table('shared.module_registry_state')->where('id', 1)->first()->update_operation ?? null) !== null || $this->connection->table('shared.module_transitions')->whereIn('status', self::ACTIVE_TRANSITIONS)->exists()) {
                throw new RuntimeException('A module transition requires completion or recovery.');
            }
            /** @var object{owner: string, registered: bool, kind: string}|null $row */
            $row = $this->connection->table('shared.modules')->where('module_id', $id)->first();
            if ($row === null || $row->registered) {
                throw new RuntimeException('An unregistered module provenance record is required.');
            }
            foreach ($this->discovery->discover($row->owner) as $source) {
                if ($source->manifest->id !== $id) {
                    continue;
                }
                if ($source->manifest->kind !== $row->kind || $row->kind === 'core') {
                    throw new RuntimeException('Retained module identity cannot change.');
                }
                $this->connection->table('shared.modules')->where('module_id', $id)->update([
                    'public_id' => (string) Str::uuid7(), 'registered' => true, 'source_present' => true, 'enabled' => false,
                    'source_version' => $source->manifest->version, 'manifest_hash' => $source->manifestHash,
                    'manifest' => json_encode((object) $source->manifest->definition, JSON_THROW_ON_ERROR), 'updated_at' => now(),
                ]);
                $this->connection->table('shared.module_registry_state')->where('id', 1)->increment('generation');

                return;
            }
            throw new RuntimeException('Compatible owned module source is required.');
        });

        return $this->snapshot();
    }

    public function snapshot(): RegistrySnapshot
    {
        $this->connection->reconnectIfMissingConnection();
        $pdo = $this->connection->getPdo();
        $level = $this->connection->transactionLevel();
        if (($this->cachedLevel !== null && $this->cachedLevel > $level) || $this->cachedPdo?->get() !== $pdo) {
            $this->forgetSnapshot();
        }
        // The first transactional load holds the singleton read lock until this
        // transaction ends. Other connections cannot publish under that lock;
        // same-connection writes and replacement transactions invalidate through
        // callbacks. Read-only child savepoints can reuse the ancestor's lock.
        if ($level > 0 && $this->cached !== null) {
            return $this->cached;
        }
        /** @var int|null $generation */
        $generation = $this->connection->table('shared.module_registry_state')->where('id', 1)->value('generation');
        if ($generation === null) {
            throw new RuntimeException('Module registry migration is required.');
        }
        if ($this->cached !== null && $this->cached->generation === (int) $generation) {
            return $this->cached;
        }
        $snapshot = $level > 0 ? $this->loadSnapshot() : $this->connection->transaction($this->loadSnapshot(...));
        $this->cached = $snapshot;
        $this->cachedLevel = $level;
        $this->cachedPdo = WeakReference::create($pdo);

        return $snapshot;
    }

    private function loadSnapshot(): RegistrySnapshot
    {
        // Also acquire this lock for a caller-owned transaction. A read begun
        // outside a transaction takes its own short transaction for consistency.
        /** @var object{generation:int,update_operation:?string}|null $state */
        $state = $this->connection->table('shared.module_registry_state')->where('id', 1)->sharedLock()->first(['generation', 'update_operation']);
        if ($state === null) {
            throw new RuntimeException('Module registry migration is required.');
        }
        $modules = [];
        /** @var list<object{public_id: string, module_id: string, kind: string, registered: bool, source_present: bool, enabled: bool, source_version: string, installed_version: ?string, source_path: string, manifest_hash: string, manifest: string, health_observations: string}> $rows */
        $rows = $this->connection->table('shared.modules')->orderBy('module_id')->get()->all();
        foreach ($rows as $row) {
            /** @var array{dependencies: array{required: array<string, string>, optional: array<string, string>}, platform: string, framework_api: string} $manifest Validated during explicit synchronization. */
            $manifest = json_decode($row->manifest, true, 16, JSON_THROW_ON_ERROR);
            /** @var array<string, array{fingerprint: string, passed: bool, checked_at: int, expires_at: int, code: string}> $observations */
            $observations = json_decode($row->health_observations, true, 16, JSON_THROW_ON_ERROR);
            $modules[$row->module_id] = new ModuleRecord($row->public_id, $row->module_id, $row->kind, $row->registered, $row->source_present, $row->kind === 'core' || $row->enabled, $row->source_version, $row->installed_version, $row->source_path, $row->manifest_hash, $manifest['dependencies']['required'], $manifest['dependencies']['optional'], $manifest['platform'], $manifest['framework_api'], $observations);
        }

        $fenced = [];
        /** @var list<string> $active */
        $active = $this->connection->table('shared.module_transitions')->whereIn('status', self::ACTIVE_TRANSITIONS)->pluck('affected')->all();
        foreach ($active as $affected) {
            /** @var list<string> $ids */
            $ids = json_decode($affected, true, 16, JSON_THROW_ON_ERROR);
            foreach ($ids as $id) {
                $fenced[$id] = true;
            }
        }
        if ($state->update_operation !== null) {
            $fenced += array_fill_keys(array_keys($modules), true);
        }

        return new RegistrySnapshot($state->generation, $modules, array_keys($fenced));
    }
}
