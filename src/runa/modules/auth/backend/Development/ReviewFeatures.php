<?php

namespace Runa\Modules\Auth\Development;

use Runa\Contracts\Modules\SettingsActor;
use Runa\Support\Modules\Settings\ModuleSettings;
use Runa\Support\Seeding\DevelopmentFixtures;

/** Explicit development seeding owns this review preset; ordinary reopen never applies it. */
final readonly class ReviewFeatures
{
    public function enable(): void
    {
        DevelopmentFixtures::assertAllowed();
        $settings = app(ModuleSettings::class);
        $state = $settings->read('runa.auth');
        $preset = array_fill_keys([
            'public_welcome_enabled', 'public_registration', 'own_name_editable', 'own_login_editable', 'own_email_editable',
            'secondary_emails_enabled', 'secondary_self_add', 'secondary_self_change', 'secondary_self_remove',
            'avatar_enabled', 'own_login_history', 'own_account_history', 'self_deactivate', 'self_delete',
            'totp_enabled', 'email_otp_enabled', 'recovery_codes_enabled', 'trusted_devices_enabled', 'session_extension',
            'admin_email_verification_bypass', 'notice_new_browser', 'notice_failed_login', 'notice_locked',
            'notice_password_changed', 'notice_account_deactivation', 'notice_password_expiry',
        ], true);
        $preset += ['avatar_editor' => 'both', 'identifier_mode' => 'either', 'two_factor_required' => 'optional', 'session_remember_minutes' => 10080];
        $patch = array_filter($preset, fn ($value, $key) => ($state['settings'][$key]['value'] ?? null) !== $value, ARRAY_FILTER_USE_BOTH);
        if ($patch === []) {
            return;
        }
        $result = $settings->update('runa.auth', $patch, $state['revision'], $state['generation'], new SettingsActor('system', 'development.auth.review'));
        if ($result->status !== 'succeeded') {
            throw new \RuntimeException('Development review settings did not complete.');
        }
    }
}
