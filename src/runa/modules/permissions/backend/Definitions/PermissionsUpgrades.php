<?php

namespace Runa\Modules\Permissions\Definitions;

use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Contracts\Modules\UpgradeProvider;
use Runa\Modules\Permissions\InitializePermissions;
use Runa\Modules\Permissions\UpgradeAccess;
use Runa\Modules\Permissions\UpgradeAuthority;
use Runa\Modules\Permissions\UpgradeAuthPresentation;
use Runa\Modules\Permissions\UpgradeCoreIntegration;
use Runa\Modules\Permissions\UpgradeDevelopmentFixtures;

final class PermissionsUpgrades implements UpgradeProvider
{
    public function upgrades(): array
    {
        return [new UpgradeDefinition('initialize', null, '0.1.0', InitializePermissions::class),
            new UpgradeDefinition('access', '0.1.0', '0.2.0', UpgradeAccess::class),
            new UpgradeDefinition('authority', '0.2.0', '0.3.0', UpgradeAuthority::class),
            new UpgradeDefinition('core_integration', '0.3.0', '0.4.0', UpgradeCoreIntegration::class),
            new UpgradeDefinition('development_fixtures', '0.4.0', '0.5.0', UpgradeDevelopmentFixtures::class),
            new UpgradeDefinition('auth_presentation', '0.5.0', '0.5.1', UpgradeAuthPresentation::class)];
    }

    public function preflight(?string $installed, string $target): ?string
    {
        return null;
    }
}
