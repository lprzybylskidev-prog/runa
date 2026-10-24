<?php

namespace Runa\Modules\Permissions;

use Illuminate\Support\Str;
use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Contracts\AuditWriter;
use Runa\Modules\Permissions\Actions\SynchronizeDeclarations;
use Runa\Support\Modules\Api\ModuleDeclarations;
use Runa\Support\Modules\Data\AuthorityLock;
use Runa\Support\Modules\ModuleRegistry;

final class InitializePermissions implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        $connection = $context->connection;
        $registry = app(ModuleRegistry::class);
        $declarations = app(ModuleDeclarations::class);
        $sync = app(SynchronizeDeclarations::class);
        app(AuthorityLock::class)->acquire($connection);
        $record = $registry->snapshot()->modules['runa.audit'] ?? throw new \RuntimeException('Audit source unavailable.');
        $writer = $declarations->make($record, 'Runa\\Modules\\Audit\\Storage\\AppendEvents', AuditWriter::class);
        if (! $writer instanceof AuditWriter) {
            throw new \RuntimeException('Required Audit writer unavailable.');
        }
        $system = $connection->table('runa_permissions.roles')->where('code', 'runa.permissions.super_admin')->lockForUpdate()->first();
        if ($system !== null && ($system->module_id !== 'runa.permissions' || ! $system->system || $system->deleted_at !== null)) {
            throw new \RuntimeException('Protected system-role identity conflicts with retained data.');
        }
        if ($system === null) {
            $connection->table('runa_permissions.roles')->insert([
                'public_id' => (string) Str::uuid7(), 'code' => 'runa.permissions.super_admin', 'module_id' => 'runa.permissions', 'system' => true,
                'name_pl' => 'Superadministrator', 'name_en' => 'Super administrator',
                'description_pl' => 'Wszystkie zwykłe uprawnienia akcji, z zachowaniem kontroli bezpieczeństwa.',
                'description_en' => 'All ordinary action grants, with independent security controls.',
                'created_at' => now('UTC'), 'updated_at' => now('UTC'),
            ]);
            $writer->record($connection, new AuditEvent('runa.permissions', 'system_role.created', context: ['code' => 'runa.permissions.super_admin']));
        }
        // Install catches up existing module definitions, including disabled modules; no users are assigned.
        foreach ($registry->snapshot()->modules as $module) {
            if ($module->registered && $module->sourcePresent) {
                $sync->apply($connection, $sync->preview($module->id), $writer);
            }
        }
    }
}
