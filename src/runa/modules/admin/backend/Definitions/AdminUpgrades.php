<?php

namespace Runa\Modules\Admin\Definitions;

use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Contracts\Modules\UpgradeProvider;
use Runa\Modules\Admin\InitializeAdmin;
use Runa\Modules\Admin\UpdateAdmin;

final class AdminUpgrades implements UpgradeProvider
{
    public function upgrades(): array
    {
        return [
            new UpgradeDefinition('initialize', null, '2.0.0', InitializeAdmin::class),
            new UpgradeDefinition('current_initialization', '1.1.0', '2.0.0', UpdateAdmin::class),
            new UpgradeDefinition('published_1_0_0', '1.0.0', '2.0.0', UpdateAdmin::class),
        ];
    }

    public function preflight(?string $installed, string $target): ?string
    {
        return null;
    }
}
