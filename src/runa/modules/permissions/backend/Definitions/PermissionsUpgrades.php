<?php

namespace Runa\Modules\Permissions\Definitions;

use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Contracts\Modules\UpgradeProvider;
use Runa\Modules\Permissions\InitializePermissions;
use Runa\Modules\Permissions\UpgradeAccess;
use Runa\Modules\Permissions\UpgradeAuthority;

final class PermissionsUpgrades implements UpgradeProvider
{
    public function upgrades(): array
    {
        return [new UpgradeDefinition('initialize', null, '0.1.0', InitializePermissions::class),
            new UpgradeDefinition('access', '0.1.0', '0.2.0', UpgradeAccess::class),
            new UpgradeDefinition('authority', '0.2.0', '0.3.0', UpgradeAuthority::class)];
    }

    public function preflight(?string $installed, string $target): ?string
    {
        return null;
    }
}
