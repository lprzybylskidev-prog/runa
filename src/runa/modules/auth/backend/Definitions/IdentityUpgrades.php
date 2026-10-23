<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Contracts\Modules\UpgradeProvider;
use Runa\Modules\Auth\InitializeAuth;
use Runa\Modules\Auth\UpgradeEntry;

final class IdentityUpgrades implements UpgradeProvider
{
    public function upgrades(): array
    {
        return [new UpgradeDefinition('initialize', null, '0.1.0', InitializeAuth::class),
            new UpgradeDefinition('entry', '0.1.0', '0.2.0', UpgradeEntry::class)];
    }

    public function preflight(?string $installed, string $target): ?string
    {
        return null;
    }
}
