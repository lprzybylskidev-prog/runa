<?php

namespace Runa\Modules\Auth\Support;

use Runa\Modules\Auth\Contracts\BulkUserChange;

/** Source-owned catalogue; command visibility never replaces native admission. */
final class BulkUserOperations
{
    public static function proof(string $operation): string
    {
        return match ($operation) {
            'delete', 'restore', 'password_reset', 'factor_reset', 'revoke_exceptions' => 'password',
            'activate', 'deactivate', 'schedule_activation', 'schedule_deactivation', 'set_attempt_lock', 'set_security_lock', 'require_password_change', 'invite', 'invalidate_invitation', 'require_verification', 'revoke_trust', 'reset_preferences', 'remove_avatar' => 'none',
            default => throw new \InvalidArgumentException('Unknown bulk operation.'),
        };
    }

    /** @return array{operation:string,permission:string} */
    public static function access(string $operation): array
    {
        if (in_array($operation, BulkUserChange::LIFECYCLE, true)) {
            return ['operation' => 'identity.change', 'permission' => 'users.manage'];
        }
        [$name,$permission] = match ($operation) {
            'invite' => ['invitation.issue', 'invitations.manage'],
            'invalidate_invitation' => ['invitation.invalidate', 'invitations.manage'],
            'require_verification' => ['email.require_verification', 'emails.reverify'],
            'password_reset' => ['password.admin_reset', 'passwords.reset'],
            'factor_reset' => ['two_factor.admin_reset', 'two_factor.reset'],
            'revoke_trust' => ['trust.revoke', 'trust.manage'],
            'reset_preferences' => ['preferences.admin_reset', 'preferences.reset'],
            'remove_avatar' => ['avatar.admin_remove', 'avatars.manage'],
            'revoke_exceptions' => ['policy.exception', 'policy.exceptions'],
            default => throw new \InvalidArgumentException('Unknown bulk operation.'),
        };

        return ['operation' => $name, 'permission' => $permission];
    }

    public static function available(string $operation): bool
    {
        $feature = match ($operation) {
            'require_verification' => 'verification', 'require_password_change' => 'password_expiry',
            'factor_reset' => 'factors', 'revoke_trust' => 'trust', 'remove_avatar' => 'avatar', default => null,
        };

        return $feature === null || app(AdministrationFeatures::class)->enabled($feature);
    }

    public static function delivers(string $operation): bool
    {
        return in_array($operation, ['invite', 'require_verification', 'password_reset'], true);
    }
}
