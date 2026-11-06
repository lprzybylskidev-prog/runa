<?php

namespace Runa\Modules\Admin\Definitions;

use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Contracts\Modules\UpgradeProvider;
use Runa\Modules\Admin\InitializeAdmin;

final class AdminUpgrades implements UpgradeProvider
{
    public function upgrades(): array
    {
        return [
            new UpgradeDefinition('initialize', null, '0.1.0', InitializeAdmin::class),
            new UpgradeDefinition('standalone_participants', '0.1.0', '0.2.0', InitializeAdmin::class),
            new UpgradeDefinition('creation_release', '0.2.0', '1.0.0', InitializeAdmin::class),
        ];
    }

    public function preflight(?string $installed, string $target): ?string
    {
        return null;
    }
}
