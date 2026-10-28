<?php

namespace Runa\Modules\Auth\Support;

use Runa\Modules\Auth\Models\User;

final class AccountHistoryState
{
    /**
     * @return array{authenticator_enabled: bool, email_factor: bool, setup_pending: bool, spare_codes_remaining: int}
     */
    public static function factors(User $user): array
    {
        return ['authenticator_enabled' => $user->totp_secret !== null, 'email_factor' => $user->email_factor,
            'setup_pending' => $user->totp_pending !== null, 'spare_codes_remaining' => count($user->recovery_hashes ?? [])];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public static function pairs(array $before, array $after): array
    {
        $changes = [];
        foreach ($after as $field => $value) {
            $changes[$field] = ['old' => $before[$field] ?? null, 'new' => $value];
        }

        return $changes;
    }
}
