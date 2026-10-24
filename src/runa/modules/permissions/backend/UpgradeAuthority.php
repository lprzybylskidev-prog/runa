<?php

namespace Runa\Modules\Permissions;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Source-only authority APIs; reserved preparation synchronizes new permissions. */
final class UpgradeAuthority implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        if (! $context->connection->table('runa_permissions.roles')->where('code', 'runa.permissions.super_admin')->where('system', true)->whereNull('deleted_at')->exists()) {
            throw new \RuntimeException('Protected system role unavailable.');
        }
    }
}
