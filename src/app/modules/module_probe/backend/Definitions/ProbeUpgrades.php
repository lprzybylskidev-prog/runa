<?php

namespace App\Modules\ModuleProbe\Definitions;

use App\Modules\ModuleProbe\InitializeProbe;
use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Contracts\Modules\UpgradeProvider;

final class ProbeUpgrades implements UpgradeProvider
{
    public function upgrades(): array
    {
        return [
            new UpgradeDefinition('initialize', null, '0.9.0', InitializeProbe::class),
            new UpgradeDefinition('record_update', '0.8.1', '0.9.0', InitializeProbe::class),
        ];
    }

    public function preflight(?string $installed, string $target): ?string
    {
        return null;
    }
}
