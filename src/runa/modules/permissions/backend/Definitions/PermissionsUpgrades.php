<?php

namespace Runa\Modules\Permissions\Definitions;

use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Contracts\Modules\UpgradeProvider;
use Runa\Modules\Permissions\InitializePermissions;
use Runa\Modules\Permissions\UpdatePermissions;

final class PermissionsUpgrades implements UpgradeProvider
{
    public function upgrades(): array
    {
        return [
            new UpgradeDefinition('initialize', null, '2.1.2', InitializePermissions::class),
            new UpgradeDefinition('current_initialization', '2.1.1', '2.1.2', UpdatePermissions::class),
        ];
    }

    public function preflight(?string $installed, string $target): ?string
    {
        return null;
    }
}
