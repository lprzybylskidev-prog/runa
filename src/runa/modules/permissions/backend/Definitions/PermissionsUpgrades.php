<?php

namespace Runa\Modules\Permissions\Definitions;

use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Contracts\Modules\UpgradeProvider;
use Runa\Modules\Permissions\InitializePermissions;
use Runa\Modules\Permissions\UpgradeAccess;
use Runa\Modules\Permissions\UpgradeAuthAllPreferences;
use Runa\Modules\Permissions\UpgradeAuthAvatar;
use Runa\Modules\Permissions\UpgradeAuthCards;
use Runa\Modules\Permissions\UpgradeAuthFactorDialogs;
use Runa\Modules\Permissions\UpgradeAuthority;
use Runa\Modules\Permissions\UpgradeAuthPresentation;
use Runa\Modules\Permissions\UpgradeAuthQr;
use Runa\Modules\Permissions\UpgradeAuthRecoveryDialog;
use Runa\Modules\Permissions\UpgradeAuthRefresh;
use Runa\Modules\Permissions\UpgradeAuthSecondaryDetails;
use Runa\Modules\Permissions\UpgradeCoreIntegration;
use Runa\Modules\Permissions\UpgradeDevelopmentFixtures;
use Runa\Modules\Permissions\UpgradeTestLayout;

final class PermissionsUpgrades implements UpgradeProvider
{
    public function upgrades(): array
    {
        return [new UpgradeDefinition('initialize', null, '0.1.0', InitializePermissions::class),
            new UpgradeDefinition('access', '0.1.0', '0.2.0', UpgradeAccess::class),
            new UpgradeDefinition('authority', '0.2.0', '0.3.0', UpgradeAuthority::class),
            new UpgradeDefinition('core_integration', '0.3.0', '0.4.0', UpgradeCoreIntegration::class),
            new UpgradeDefinition('development_fixtures', '0.4.0', '0.5.0', UpgradeDevelopmentFixtures::class),
            new UpgradeDefinition('auth_presentation', '0.5.0', '0.5.1', UpgradeAuthPresentation::class),
            new UpgradeDefinition('auth_cards', '0.5.1', '0.5.2', UpgradeAuthCards::class),
            new UpgradeDefinition('auth_avatar', '0.5.2', '0.5.3', UpgradeAuthAvatar::class),
            new UpgradeDefinition('auth_qr', '0.5.3', '0.5.4', UpgradeAuthQr::class),
            new UpgradeDefinition('auth_recovery_dialog', '0.5.4', '0.5.5', UpgradeAuthRecoveryDialog::class),
            new UpgradeDefinition('auth_secondary_details', '0.5.5', '0.5.6', UpgradeAuthSecondaryDetails::class),
            new UpgradeDefinition('auth_factor_dialogs', '0.5.6', '0.5.7', UpgradeAuthFactorDialogs::class),
            new UpgradeDefinition('auth_all_preferences', '0.5.7', '0.5.8', UpgradeAuthAllPreferences::class),
            new UpgradeDefinition('auth_refresh', '0.5.8', '0.5.9', UpgradeAuthRefresh::class),
            new UpgradeDefinition('history_dependencies', '0.5.9', '0.5.10', UpgradeAuthRefresh::class),
            new UpgradeDefinition('history_capture_dependencies', '0.5.10', '0.5.11', UpgradeAuthRefresh::class),
            new UpgradeDefinition('stable_release', '0.5.11', '1.0.0', UpgradeAuthRefresh::class),
            new UpgradeDefinition('central_test_layout', '1.0.0', '1.0.1', UpgradeTestLayout::class)];
    }

    public function preflight(?string $installed, string $target): ?string
    {
        return null;
    }
}
