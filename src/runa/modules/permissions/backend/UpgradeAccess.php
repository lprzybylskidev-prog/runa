<?php

namespace Runa\Modules\Permissions;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

final class UpgradeAccess implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        foreach (['presets', 'preset_roles', 'preset_permissions', 'user_access'] as $table) {
            if (! $context->connection->getSchemaBuilder()->hasTable('runa_permissions.'.$table)) {
                throw new \RuntimeException('Permissions access storage unavailable.');
            }
        }
    }
}
