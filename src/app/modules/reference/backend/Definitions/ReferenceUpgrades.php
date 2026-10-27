<?php

namespace App\Modules\Reference\Definitions;

use App\Modules\Reference\InitializeReference;
use App\Modules\Reference\UpgradeTestLayout;
use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Contracts\Modules\UpgradeProvider;

final class ReferenceUpgrades implements UpgradeProvider
{
    public function upgrades(): array
    {
        return [
            new UpgradeDefinition('initialize', null, '0.1.0', InitializeReference::class),
            new UpgradeDefinition('central_test_layout', '0.1.0', '0.1.1', UpgradeTestLayout::class),
        ];
    }

    public function preflight(?string $installed, string $target): ?string
    {
        return null;
    }
}
