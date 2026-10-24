<?php

namespace Runa\Modules\Permissions\Definitions;

use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Contracts\Modules\UpgradeProvider;
use Runa\Modules\Permissions\InitializePermissions;

final class PermissionsUpgrades implements UpgradeProvider
{
    public function upgrades(): array
    {
        return [new UpgradeDefinition('initialize', null, '0.1.0', InitializePermissions::class)];
    }

    public function preflight(?string $installed, string $target): ?string
    {
        return null;
    }
}
