<?php

namespace Runa\Modules\Auth\Support;

use Carbon\CarbonImmutable;
use Runa\Modules\Auth\Models\User;
use Runa\Support\Modules\Settings\ModuleSettings;

final readonly class CredentialPolicy
{
    public function __construct(private ModuleSettings $settings) {}

    public function value(string $key): mixed
    {
        return $this->settings->value('runa.auth', $key);
    }

    public function permitted(string $method): bool
    {
        return match ($method) {
            'totp', 'recovery' => $this->value('totp_enabled'),
            'email' => $this->value('email_otp_enabled'),
            default => false,
        };
    }

    public function requiredMethod(): string
    {
        return $this->value('two_factor_required');
    }

    public function enrolled(User $user): array
    {
        return array_values(array_filter(['totp', 'email'], fn ($method) => $this->permitted($method)
            && ($method === 'totp' ? $user->totp_secret !== null : $user->email_factor && $user->email_verified_at !== null)));
    }

    public function accepts(string $method): bool
    {
        return $this->permitted($method) && ($this->requiredMethod() !== 'totp' || in_array($method, ['totp', 'recovery'], true))
            && ($this->requiredMethod() !== 'email' || $method === 'email');
    }

    public function needsFactor(User $user): bool
    {
        return $this->requiredMethod() !== 'optional' || $this->enrolled($user) !== [];
    }

    public function restrictions(User $user, ?object $session): array
    {
        if (! $this->needsFactor($user)) {
            return [];
        }
        $enrolled = array_filter($this->enrolled($user), fn ($method) => $this->accepts($method));
        if ($enrolled === []) {
            return ['two_factor_enrollment_required'];
        }

        return $session !== null && $session->factor_verified_at !== null && $this->accepts($session->factor_method ?? '')
            ? [] : ['two_factor_required'];
    }

    public function passwordExpired(User $user): bool
    {
        $days = $this->value('password_expiry_days');

        return $user->password !== null && $days > 0 && ($user->password_changed_at === null
            || CarbonImmutable::now('UTC')->gte($user->password_changed_at->addDays($days)));
    }
}
