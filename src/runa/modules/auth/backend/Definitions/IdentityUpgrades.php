<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Contracts\Modules\UpgradeProvider;
use Runa\Modules\Auth\InitializeAuth;
use Runa\Modules\Auth\UpgradeAccountCards;
use Runa\Modules\Auth\UpgradeAccountCopy;
use Runa\Modules\Auth\UpgradeAccountEditor;
use Runa\Modules\Auth\UpgradeAccountMethods;
use Runa\Modules\Auth\UpgradeAccountOrchestration;
use Runa\Modules\Auth\UpgradeAccountRefresh;
use Runa\Modules\Auth\UpgradeAccountUi;
use Runa\Modules\Auth\UpgradeAllPreferences;
use Runa\Modules\Auth\UpgradeAuthorityParticipation;
use Runa\Modules\Auth\UpgradeAvatarModal;
use Runa\Modules\Auth\UpgradeCoreIntegration;
use Runa\Modules\Auth\UpgradeCredentials;
use Runa\Modules\Auth\UpgradeDevelopmentFixtures;
use Runa\Modules\Auth\UpgradeEntry;
use Runa\Modules\Auth\UpgradeEntryUi;
use Runa\Modules\Auth\UpgradeExecutionLayout;
use Runa\Modules\Auth\UpgradeFactorDialogs;
use Runa\Modules\Auth\UpgradeFactorSpacing;
use Runa\Modules\Auth\UpgradeFormNotifications;
use Runa\Modules\Auth\UpgradeFormPresentation;
use Runa\Modules\Auth\UpgradeFrontendContracts;
use Runa\Modules\Auth\UpgradeHeadingFocus;
use Runa\Modules\Auth\UpgradeMailLayout;
use Runa\Modules\Auth\UpgradeNavigationCancellation;
use Runa\Modules\Auth\UpgradePermissionDeclarations;
use Runa\Modules\Auth\UpgradePlatformComposition;
use Runa\Modules\Auth\UpgradePlatformDelivery;
use Runa\Modules\Auth\UpgradePolicy;
use Runa\Modules\Auth\UpgradePreferences;
use Runa\Modules\Auth\UpgradePreferencesPresentation;
use Runa\Modules\Auth\UpgradePreferencesSelection;
use Runa\Modules\Auth\UpgradePresentation;
use Runa\Modules\Auth\UpgradeProfiles;
use Runa\Modules\Auth\UpgradeRecoveryDialog;
use Runa\Modules\Auth\UpgradeSecondaryDetails;
use Runa\Modules\Auth\UpgradeSessions;
use Runa\Modules\Auth\UpgradeSessionSorting;
use Runa\Modules\Auth\UpgradeShellAvatar;
use Runa\Modules\Auth\UpgradeTestLayout;
use Runa\Modules\Auth\UpgradeTotpQr;
use Runa\Modules\Auth\UpgradeUserPanel;
use Runa\Modules\Auth\UpgradeWelcomeHeader;
use Runa\Modules\Auth\UpgradeWelcomeLinks;

final class IdentityUpgrades implements UpgradeProvider
{
    public function upgrades(): array
    {
        return [new UpgradeDefinition('initialize', null, '0.1.0', InitializeAuth::class),
            new UpgradeDefinition('entry', '0.1.0', '0.2.0', UpgradeEntry::class),
            new UpgradeDefinition('sessions', '0.2.0', '0.3.0', UpgradeSessions::class),
            new UpgradeDefinition('credentials', '0.3.0', '0.4.0', UpgradeCredentials::class),
            new UpgradeDefinition('profiles', '0.4.0', '0.5.0', UpgradeProfiles::class),
            new UpgradeDefinition('preferences', '0.5.0', '0.6.0', UpgradePreferences::class),
            new UpgradeDefinition('policy', '0.6.0', '0.7.0', UpgradePolicy::class),
            new UpgradeDefinition('entry_ui', '0.7.0', '0.8.0', UpgradeEntryUi::class),
            new UpgradeDefinition('account_ui', '0.8.0', '0.9.0', UpgradeAccountUi::class),
            new UpgradeDefinition('permission_declarations', '0.9.0', '0.10.0', UpgradePermissionDeclarations::class),
            new UpgradeDefinition('authority_participation', '0.10.0', '0.10.1', UpgradeAuthorityParticipation::class),
            new UpgradeDefinition('core_integration', '0.10.1', '0.11.0', UpgradeCoreIntegration::class),
            new UpgradeDefinition('development_fixtures', '0.11.0', '0.12.0', UpgradeDevelopmentFixtures::class),
            new UpgradeDefinition('presentation', '0.12.0', '0.13.0', UpgradePresentation::class),
            new UpgradeDefinition('welcome_header', '0.13.0', '0.13.1', UpgradeWelcomeHeader::class),
            new UpgradeDefinition('welcome_links', '0.13.1', '0.13.2', UpgradeWelcomeLinks::class),
            new UpgradeDefinition('heading_focus', '0.13.2', '0.13.3', UpgradeHeadingFocus::class),
            new UpgradeDefinition('form_presentation', '0.13.3', '0.13.4', UpgradeFormPresentation::class),
            new UpgradeDefinition('user_panel', '0.13.4', '0.13.5', UpgradeUserPanel::class),
            new UpgradeDefinition('navigation_cancellation', '0.13.5', '0.13.6', UpgradeNavigationCancellation::class),
            new UpgradeDefinition('account_editor', '0.13.6', '0.13.7', UpgradeAccountEditor::class),
            new UpgradeDefinition('account_cards', '0.13.7', '0.14.0', UpgradeAccountCards::class),
            new UpgradeDefinition('avatar_modal', '0.14.0', '0.14.1', UpgradeAvatarModal::class),
            new UpgradeDefinition('form_notifications', '0.14.1', '0.14.2', UpgradeFormNotifications::class),
            new UpgradeDefinition('shell_avatar', '0.14.2', '0.15.0', UpgradeShellAvatar::class),
            new UpgradeDefinition('account_methods', '0.15.0', '0.15.1', UpgradeAccountMethods::class),
            new UpgradeDefinition('totp_qr', '0.15.1', '0.16.0', UpgradeTotpQr::class),
            new UpgradeDefinition('recovery_dialog', '0.16.0', '0.17.0', UpgradeRecoveryDialog::class),
            new UpgradeDefinition('secondary_details', '0.17.0', '0.18.0', UpgradeSecondaryDetails::class),
            new UpgradeDefinition('mail_layout', '0.18.0', '0.18.1', UpgradeMailLayout::class),
            new UpgradeDefinition('session_sorting', '0.18.1', '0.18.2', UpgradeSessionSorting::class),
            new UpgradeDefinition('factor_dialogs', '0.18.2', '0.19.0', UpgradeFactorDialogs::class),
            new UpgradeDefinition('preferences_presentation', '0.19.0', '0.19.1', UpgradePreferencesPresentation::class),
            new UpgradeDefinition('all_preferences', '0.19.1', '0.20.0', UpgradeAllPreferences::class),
            new UpgradeDefinition('preferences_selection', '0.20.0', '0.20.1', UpgradePreferencesSelection::class),
            new UpgradeDefinition('account_refresh', '0.20.1', '0.21.0', UpgradeAccountRefresh::class),
            new UpgradeDefinition('account_copy', '0.21.0', '0.21.1', UpgradeAccountCopy::class),
            new UpgradeDefinition('factor_spacing', '0.21.1', '0.21.2', UpgradeFactorSpacing::class),
            new UpgradeDefinition('trust_heading', '0.21.2', '0.21.3', UpgradeFactorSpacing::class),
            new UpgradeDefinition('secondary_status_layout', '0.21.3', '0.21.4', UpgradeFactorSpacing::class),
            new UpgradeDefinition('frontend_type_safety', '0.21.4', '0.21.5', UpgradeFactorSpacing::class),
            new UpgradeDefinition('structural_loading', '0.21.5', '0.21.6', UpgradeFactorSpacing::class),
            new UpgradeDefinition('table_loading', '0.21.6', '0.21.7', UpgradeFactorSpacing::class),
            new UpgradeDefinition('single_table_read', '0.21.7', '0.21.8', UpgradeFactorSpacing::class),
            new UpgradeDefinition('welcome_modules', '0.21.8', '0.21.9', UpgradeFactorSpacing::class),
            new UpgradeDefinition('avatar_loading', '0.21.9', '0.21.10', UpgradeFactorSpacing::class),
            new UpgradeDefinition('welcome_navigation', '0.21.10', '0.21.11', UpgradeFactorSpacing::class),
            new UpgradeDefinition('account_action_tones', '0.21.11', '0.21.12', UpgradeFactorSpacing::class),
            new UpgradeDefinition('targeted_throttling', '0.21.12', '0.21.13', UpgradeFactorSpacing::class),
            new UpgradeDefinition('preference_history', '0.21.13', '0.21.14', UpgradeFactorSpacing::class),
            new UpgradeDefinition('preference_history_labels', '0.21.14', '0.21.15', UpgradeFactorSpacing::class),
            new UpgradeDefinition('history_details', '0.21.15', '0.22.0', UpgradeFactorSpacing::class),
            new UpgradeDefinition('concrete_history', '0.22.0', '0.23.0', UpgradeFactorSpacing::class),
            new UpgradeDefinition('registration_confirmation', '0.23.0', '0.23.1', UpgradeFactorSpacing::class),
            new UpgradeDefinition('stable_release', '0.23.1', '1.0.0', UpgradeFactorSpacing::class),
            new UpgradeDefinition('central_test_layout', '1.0.0', '1.0.1', UpgradeTestLayout::class),
            new UpgradeDefinition('platform_composition', '1.0.1', '1.0.2', UpgradePlatformComposition::class),
            new UpgradeDefinition('execution_layout', '1.0.2', '1.0.3', UpgradeExecutionLayout::class),
            new UpgradeDefinition('frontend_contracts', '1.0.3', '1.1.0', UpgradeFrontendContracts::class),
            new UpgradeDefinition('account_orchestration', '1.1.0', '1.1.1', UpgradeAccountOrchestration::class),
            new UpgradeDefinition('platform_delivery', '1.1.1', '1.1.2', UpgradePlatformDelivery::class)];
    }

    public function preflight(?string $installed, string $target): ?string
    {
        return null;
    }
}
