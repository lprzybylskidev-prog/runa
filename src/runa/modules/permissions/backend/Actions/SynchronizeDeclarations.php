<?php

namespace Runa\Modules\Permissions\Actions;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Runa\ModuleFramework\Data\AuthorityLock;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Contracts\AuditWriter;
use Runa\Modules\Permissions\Contracts\DeclarationPreview;
use Runa\Modules\Permissions\Support\DeclarationCatalog;

/** Trusted install/update only; no ordinary mutation API and no assignment side effects. */
final readonly class SynchronizeDeclarations
{
    public function __construct(private DatabaseManager $database, private DeclarationCatalog $catalog, private ModuleRegistry $registry, private AuthorityLock $authority) {}

    public function preview(string $module): DeclarationPreview
    {
        $connection = $this->database->connection();
        $declared = $this->catalog->read($module);
        $changes = [];
        $affected = [];
        foreach (['permissions', 'roles'] as $table) {
            foreach ($declared[$table] as $code => $definition) {
                $dictionary = $table === 'roles' ? $definition['dictionary'] : $definition;
                $row = $connection->table('runa_permissions.'.$table)->where('code', $code)->first();
                if ($row !== null && ($row->module_id !== $module || $row->system || $row->deleted_at !== null)) {
                    throw new \RuntimeException('Declaration identity is protected or owned elsewhere.');
                }
                $old = $row === null ? null : array_intersect_key((array) $row, $dictionary);
                $before = $after = [];
                if ($table === 'roles') {
                    $before = $row === null ? [] : $connection->table('runa_permissions.role_permissions as rp')
                        ->join('runa_permissions.permissions as p', 'p.id', '=', 'rp.permission_id')->where('rp.role_id', $row->id)
                        ->orderBy('p.code')->pluck('p.code')->all();
                    $after = $definition['permissions'];
                }
                if ($old !== $dictionary || $before !== $after) {
                    $changes[$table][$code] = ['before' => $old, 'after' => $dictionary, 'version' => $row?->version,
                        'members_before' => $before, 'members_after' => $after];
                    if ($row !== null && $table === 'roles' && $before !== $after) {
                        $affected[] = $row->id;
                    }
                }
            }
        }
        $record = $this->registry->snapshot()->modules[$module] ?? throw new \RuntimeException('Declaration owner missing.');
        $assignments = $affected === [] ? [] : $connection->table('runa_permissions.user_roles')
            ->whereIn('role_id', $affected)->orderBy('user_public_id')->orderBy('role_id')->get(['role_id', 'user_public_id'])->all();
        $fingerprint = hash('sha256', json_encode([$record->manifestHash, $declared, $changes, $assignments], JSON_THROW_ON_ERROR));

        return new DeclarationPreview($module, $fingerprint, $changes, count(array_unique(array_map(static fn (object $row): string => $row->user_public_id, $assignments))));
    }

    public function apply(Connection $connection, DeclarationPreview $reviewed, AuditWriter $audit): void
    {
        if ($connection !== $this->database->connection() || $connection->transactionLevel() < 1 || $connection->getDriverName() !== 'pgsql') {
            throw new \RuntimeException('Declaration sync requires the default PostgreSQL transaction.');
        }
        // Nested savepoint prevents caught Audit failures retaining partial dictionary changes.
        $connection->transaction(function () use ($connection, $reviewed, $audit): void {
            $connection->table('shared.module_registry_state')->where('id', 1)->sharedLock()->first();
            $this->authority->acquire($connection);
            $current = $this->preview($reviewed->module);
            if (! hash_equals($reviewed->fingerprint, $current->fingerprint)) {
                throw new \RuntimeException('Declaration preview is stale.');
            }
            if ($current->changes === []) {
                return;
            }
            foreach ($current->changes as $table => $definitions) {
                foreach ($definitions as $code => $change) {
                    $query = $connection->table('runa_permissions.'.$table)->where('code', $code);
                    $row = $query->lockForUpdate()->first();
                    $data = $change['after'] + ['module_id' => $reviewed->module, 'updated_at' => now('UTC')];
                    if ($row === null) {
                        $id = $connection->table('runa_permissions.'.$table)->insertGetId($data + ['public_id' => (string) Str::uuid7(), 'created_at' => now('UTC')]);
                    } else {
                        $id = $row->id;
                        $query->update($data + ['version' => $row->version + 1]);
                    }
                    if ($table === 'roles') {
                        $connection->table('runa_permissions.role_permissions')->where('role_id', $id)->delete();
                        foreach ($change['members_after'] as $permission) {
                            $permissionId = $connection->table('runa_permissions.permissions')->where('code', $permission)->whereNull('deleted_at')->value('id')
                                ?? throw new \RuntimeException('Declared role permission missing.');
                            $connection->table('runa_permissions.role_permissions')->insert(['role_id' => $id, 'permission_id' => $permissionId, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
                        }
                    }
                    // One bounded event per definition: large declarations cannot exceed Audit bounds.
                    $audit->record($connection, new AuditEvent('runa.permissions', 'declaration.synchronized', changes: $change,
                        context: ['module' => $reviewed->module, 'code' => $code, 'kind' => $table, 'affected_users' => $current->affectedUsers, 'fingerprint' => $current->fingerprint]));
                }
            }
        });
    }
}
