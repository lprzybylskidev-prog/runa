<?php

namespace Runa\Modules\Permissions;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Current-schema update; native preparation owns declaration synchronization and Audit. */
final class UpdatePermissions implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        if (! $context->connection->table('runa_permissions.roles')->where('code', 'runa.permissions.super_admin')->where('module_id', 'runa.permissions')->where('system', true)->whereNull('deleted_at')->exists()) {
            throw new \RuntimeException('Protected system role unavailable.');
        }
    }
}
