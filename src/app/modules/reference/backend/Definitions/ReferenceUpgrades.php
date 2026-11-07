<?php

namespace App\Modules\Reference\Definitions;

use App\Modules\Reference\InitializeReference;
use App\Modules\Reference\UpgradeAdminShowcase;
use App\Modules\Reference\UpgradeCoreExamples;
use App\Modules\Reference\UpgradePublicHttp;
use App\Modules\Reference\UpgradeSourceHealth;
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
            new UpgradeDefinition('public_http', '0.1.1', '0.1.2', UpgradePublicHttp::class),
            new UpgradeDefinition('source_health', '0.1.2', '0.2.0', UpgradeSourceHealth::class),
            new UpgradeDefinition('admin_showcase', '0.2.0', '0.3.0', UpgradeAdminShowcase::class),
            new UpgradeDefinition('core_examples', '0.3.0', '0.4.0', UpgradeCoreExamples::class),
        ];
    }

    public function preflight(?string $installed, string $target): ?string
    {
        return null;
    }
}
