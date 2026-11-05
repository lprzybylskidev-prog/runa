<?php

namespace Runa\Support\Modules\Settings;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\ManagementRead;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\Settings\SettingsDefinitions;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Contracts\AuditRecords;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

/** Trusted storage behind source-owned authorization, registry and authority locks. */
final readonly class SettingsSnapshots
{
    public function __construct(private DatabaseManager $database, private ModuleSettings $settings,
        private ModuleRegistry $registry, private SettingsDefinitions $definitions,
        private Encrypter $encrypter, private ModuleApis $apis) {}

    /** @return list<array{public_id:string,name:string,source_revision:int,created_at:string}> */
    public function catalog(string $module, string $scope): array
    {
        $this->transaction();

        return $this->rows($module, $scope)->orderBy('name')->limit(100)->get(['public_id', 'name', 'source_revision', 'created_at'])
            ->map(fn ($row) => ['public_id' => $row->public_id, 'name' => $row->name, 'source_revision' => (int) $row->source_revision, 'created_at' => $row->created_at])->all();
    }

    /** @param list<string> $keys */
    public function capture(string $module, string $scope, array $keys, string $name, int $revision, int $generation): string
    {
        $connection = $this->transaction();
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 80 || preg_match('/[\p{C}]/u', $name) !== 0) {
            throw $this->reject($module, 'settings_snapshot_name_invalid', 422);
        }
        if ($this->rows($module, $scope)->count() >= 100 || $this->rows($module, $scope)->where('name', $name)->exists()) {
            throw $this->reject($module, 'settings_snapshot_name_or_limit', 409);
        }
        $state = $this->settings->read($module);
        if ($state['revision'] !== $revision || $state['generation'] !== $generation) {
            throw $this->reject($module, 'settings_snapshot_stale', 409);
        }
        $hash = $this->fingerprint($module, $keys);
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $state['settings'][$key]['value'];
        }
        $id = (string) Str::uuid7();
        $connection->table('shared.module_settings_snapshots')->insert([
            'public_id' => $id, 'module_id' => $module, 'scope' => $scope, 'name' => $name,
            'source_revision' => $revision, 'definition_hash' => $hash,
            'settings_values' => $this->encrypter->encrypt(json_encode($values, JSON_THROW_ON_ERROR), false),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->audit($module, 'settings.snapshot_created', $id);

        return $id;
    }

    /** @param list<string> $keys @return array<string,mixed> */
    public function load(string $module, string $scope, array $keys, string $publicId): array
    {
        $this->transaction();
        new ManagementRead($publicId);
        $row = $this->rows($module, $scope)->where('public_id', $publicId)->first()
            ?? throw $this->reject($module, 'settings_snapshot_missing', 404);
        if (! hash_equals($this->fingerprint($module, $keys), $row->definition_hash)) {
            throw $this->reject($module, 'settings_snapshot_incompatible', 409);
        }
        $values = json_decode($this->encrypter->decrypt($row->settings_values, false), true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($values) || array_diff(array_keys($values), $keys) !== [] || array_diff($keys, array_keys($values)) !== []) {
            throw $this->reject($module, 'settings_snapshot_incompatible', 409);
        }

        // Publication performs full current validation, including private values and
        // cross-field rules. Loading never substitutes source defaults for missing keys.
        return $values;
    }

    public function delete(string $module, string $scope, string $publicId): void
    {
        $this->transaction();
        new ManagementRead($publicId);
        if ($this->rows($module, $scope)->where('public_id', $publicId)->update(['deleted_at' => now(), 'updated_at' => now()]) !== 1) {
            throw $this->reject($module, 'settings_snapshot_missing', 404);
        }
        $this->audit($module, 'settings.snapshot_deleted', $publicId);
    }

    /** @param list<string> $keys */
    private function fingerprint(string $module, array $keys): string
    {
        [$definitions] = $this->definitions->for($this->registry->snapshot()->modules[$module]);
        if ($keys === [] || ! array_is_list($keys) || count(array_unique($keys)) !== count($keys)) {
            throw new \LogicException('Snapshot scopes require explicit unique keys.');
        }
        $shape = [];
        sort($keys);
        foreach ($keys as $key) {
            $definition = $definitions[$key] ?? null;
            if ($definition === null || $definition->sensitive) {
                throw new \LogicException('Snapshots require declared non-sensitive settings.');
            }
            $shape[$key] = [$definition->type, $definition->nullable, $definition->rules, $definition->application];
        }

        return hash('sha256', serialize($shape));
    }

    private function rows(string $module, string $scope): Builder
    {
        return $this->database->connection()->table('shared.module_settings_snapshots')->where('module_id', $module)->where('scope', $scope)->whereNull('deleted_at');
    }

    private function transaction(): Connection
    {
        $connection = $this->database->connection();
        if ($connection->transactionLevel() < 1) {
            throw new \LogicException('Settings snapshots require the owning locked transaction.');
        }

        return $connection;
    }

    private function audit(string $module, string $action, string $id): void
    {
        $this->apis->resolve('runa.audit', AuditRecords::class, '1.0.0')->record(new AuditEvent($module, $action, context: ['snapshot_id' => $id]));
    }

    private function reject(string $module, string $reason, int $status): OperationRejected
    {
        return new OperationRejected($reason, $module, 'settings.snapshot', $status);
    }
}
