<?php

namespace Runa\Modules\Auth\Contracts;

final readonly class BulkUserChange
{
    public const LIFECYCLE = ['activate', 'deactivate', 'delete', 'restore', 'schedule_activation', 'schedule_deactivation', 'set_attempt_lock', 'set_security_lock', 'require_password_change'];

    public const OPERATIONS = [...self::LIFECYCLE, 'invite', 'invalidate_invitation', 'require_verification', 'password_reset', 'factor_reset', 'revoke_trust', 'reset_preferences', 'remove_avatar', 'revoke_exceptions'];

    public function __construct(public string $operation, public ?string $at = null, public ?bool $enabled = null)
    {
        if (! in_array($operation, self::OPERATIONS, true)) {
            throw new \InvalidArgumentException('Unknown bulk user operation.');
        }
        if (in_array($operation, self::LIFECYCLE, true)) {
            new LifecycleChange($operation, $at, $enabled);
        } elseif ($at !== null || $enabled !== null) {
            throw new \InvalidArgumentException('Unexpected bulk parameters.');
        }
    }
}
