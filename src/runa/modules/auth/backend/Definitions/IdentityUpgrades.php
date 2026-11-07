<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Contracts\Modules\UpgradeProvider;
use Runa\Modules\Auth\InitializeAuth;
use Runa\Modules\Auth\UpdateAuth;

final class IdentityUpgrades implements UpgradeProvider
{
    public function upgrades(): array
    {
        return [
            new UpgradeDefinition('initialize', null, '6.0.1', InitializeAuth::class),
            new UpgradeDefinition('current_initialization', '6.0.0', '6.0.1', UpdateAuth::class),
        ];
    }

    public function preflight(?string $installed, string $target): ?string
    {
        return null;
    }
}
