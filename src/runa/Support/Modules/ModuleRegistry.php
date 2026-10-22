<?php

namespace Runa\Support\Modules;

use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use RuntimeException;

final class ModuleRegistry
{
    private ?RegistrySnapshot $cached = null;

    public function __construct(private readonly Connection $connection, private readonly ModuleDiscovery $discovery) {}

    /** Explicit installation/deployment operation, never called during request boot. */
    public function synchronize(string $owner): RegistrySnapshot
    {
        $this->connection->transaction(function () use ($owner): void {
            // One existing singleton serializes discovery publication across both owners.
            $generation = $this->connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->value('generation');
            if ($generation === null) {
                throw new RuntimeException('Module registry migration is required.');
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

    public function snapshot(): RegistrySnapshot
    {
        $cacheable = $this->connection->transactionLevel() === 0;
        /** @var int|null $generation */
        $generation = $this->connection->table('shared.module_registry_state')->where('id', 1)->value('generation');
        if ($generation === null) {
            throw new RuntimeException('Module registry migration is required.');
        }
        if ($cacheable && $this->cached !== null && $this->cached->generation === (int) $generation) {
            return $this->cached;
        }

        return $this->connection->transaction(function () use ($cacheable): RegistrySnapshot {
            // Hold a read lock through the row read so a generation never labels a mixed snapshot.
            /** @var int $generation */
            $generation = $this->connection->table('shared.module_registry_state')->where('id', 1)->sharedLock()->value('generation');
            $modules = [];
            /** @var list<object{public_id: string, module_id: string, kind: string, registered: bool, source_present: bool, enabled: bool, source_version: string, installed_version: ?string, source_path: string, manifest_hash: string, manifest: string}> $rows */
            $rows = $this->connection->table('shared.modules')->orderBy('module_id')->get()->all();
            foreach ($rows as $row) {
                /** @var array{dependencies: array{required: array<string, string>, optional: array<string, string>}, platform: string, framework_api: string} $manifest Validated during explicit synchronization. */
                $manifest = json_decode($row->manifest, true, 16, JSON_THROW_ON_ERROR);
                $modules[$row->module_id] = new ModuleRecord($row->public_id, $row->module_id, $row->kind, $row->registered, $row->source_present, $row->kind === 'core' || $row->enabled, $row->source_version, $row->installed_version, $row->source_path, $row->manifest_hash, $manifest['dependencies']['required'], $manifest['dependencies']['optional'], $manifest['platform'], $manifest['framework_api']);
            }

            $snapshot = new RegistrySnapshot($generation, $modules);
            // A caller-owned transaction can still roll back and reuse a generation.
            // Never retain its uncommitted rows in a worker's process cache.
            if ($cacheable) {
                $this->cached = $snapshot;
            }

            return $snapshot;
        });
    }
}
