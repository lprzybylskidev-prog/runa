<?php

namespace Runa\Modules\Auth\Support;

/** Explicit safe fields; no caller-supplied projection or credential values. */
final class AccountHistory
{
    public static function fields(): array
    {
        $factors = ['authenticator_enabled', 'email_factor', 'setup_pending', 'spare_codes_remaining', 'spare_codes_replaced'];
        $lifecycle = ['active', 'approved', 'deleted_at', 'activate_at', 'deactivate_at', 'expires_at', 'security_locked', 'attempt_locked_until', 'renewal_required'];
        $actions = [
            'profile.changed' => ['name', 'first_name', 'surname', 'login'],
            'profile.admin_changed' => ['name', 'first_name', 'surname', 'login'],
            'email.changed' => ['email', 'verified'], 'email.admin_changed' => ['email', 'verified'],
            'email.requested' => ['email'], 'email.admin_requested' => ['email'],
            'email.reverification_required' => ['email', 'verified', 'verification_required'],
            'verification.completed' => ['email', 'verified'], 'invitation.completed' => ['email', 'verified'],
            'secondary.added' => ['email'], 'secondary.changed' => ['email'], 'secondary.removed' => ['email'],
            'secondary.verified' => ['email', 'verified'],
            'avatar.changed' => ['has_avatar'], 'avatar.removed' => ['has_avatar'],
            'delivery.preferences_changed' => ['category', 'enabled', 'recipients'],
            'delivery.preferences_reset' => ['category', 'enabled', 'recipients'],
            'preferences.changed' => ['setting', 'value'], 'preferences.reset' => ['setting', 'value'],
            'preferences.reset_all' => ['notification_overrides', 'presentation_overrides'],
            'preferences.admin_reset' => ['presentation_overrides'],
            'password.changed' => [], 'password.reset' => [], 'password.admin_reset' => [],
            'policy.exception_set' => ['policy_setting', 'policy_value', 'valid_until', 'reason'],
            'policy.exception_revoked' => ['policy_setting', 'policy_value', 'valid_until', 'reason'],
        ];
        foreach (['admin_reset', 'enrollment_started', 'totp_confirm', 'email_enable', 'disable', 'recovery_rotate', 'recovery_used'] as $operation) {
            $actions['two_factor.'.$operation] = $factors;
        }
        foreach (['created', 'approve', 'activate', 'deactivate', 'delete', 'restore', 'schedule_activation', 'schedule_deactivation', 'set_expiry', 'set_attempt_lock', 'set_security_lock', 'require_password_change', 'scheduled_activated', 'scheduled_deactivated'] as $operation) {
            $actions['identity.'.$operation] = $lifecycle;
        }

        return $actions;
    }
}
