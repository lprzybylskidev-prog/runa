<?php

namespace Runa\Modules\Auth\Support;

/** Explicit safe fields; no caller-supplied projection or credential values. */
final class AccountHistory
{
    public static function fields(): array
    {
        return [
            'profile.changed' => ['name', 'first_name', 'surname', 'login'],
            'profile.admin_changed' => ['name', 'first_name', 'surname', 'login'],
            'email.changed' => ['email'], 'email.admin_changed' => ['email'],
            'email.reverification_required' => [], 'verification.completed' => [],
            'secondary.added' => ['email'], 'secondary.changed' => ['email'], 'secondary.removed' => ['email'],
            'secondary.verified' => ['email', 'verified'],
            'avatar.changed' => ['has_avatar'], 'avatar.removed' => ['has_avatar'],
            'delivery.preferences_changed' => ['category', 'enabled', 'recipients'],
            'preferences.reset_all' => ['notification_overrides'], 'preferences.admin_reset' => [],
            'password.changed' => [], 'password.reset' => [], 'password.admin_reset' => [], 'two_factor.admin_reset' => [],
            'identity.deactivate' => ['active'], 'identity.delete' => ['active', 'deleted_at'],
        ];
    }
}
