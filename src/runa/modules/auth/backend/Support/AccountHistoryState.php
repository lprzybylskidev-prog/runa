<?php

namespace Runa\Modules\Auth\Support;

use Runa\Modules\Auth\Models\User;

final class AccountHistoryState
{
    public static function factors(User $user): array
    {
        return ['authenticator_enabled' => $user->totp_secret !== null, 'email_factor' => $user->email_factor,
            'setup_pending' => $user->totp_pending !== null, 'spare_codes_remaining' => count($user->recovery_hashes ?? [])];
    }

    public static function pairs(array $before, array $after): array
    {
        $changes = [];
        foreach ($after as $field => $value) {
            $changes[$field] = ['old' => $before[$field] ?? null, 'new' => $value];
        }

        return $changes;
    }
}
