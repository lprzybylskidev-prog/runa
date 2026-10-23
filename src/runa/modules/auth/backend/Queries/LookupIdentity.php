<?php

namespace Runa\Modules\Auth\Queries;

use Carbon\CarbonImmutable;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Contracts\AuthIdentities;
use Runa\Modules\Auth\Contracts\Eligibility;
use Runa\Modules\Auth\Contracts\Identity;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\CredentialPolicy;
use Runa\Modules\Auth\Support\Identifiers;
use Runa\Support\Modules\Settings\ModuleSettings;

/** Owning private reads do not enter protected APIs recursively. Never cache eligibility. */
final readonly class LookupIdentity implements AuthIdentities
{
    public function __construct(private ModuleSettings $settings, private CredentialPolicy $credentials) {}

    public function identity(string $publicId): ?Identity
    {
        new TrustedPrincipal($publicId);

        return User::withTrashed()->where('public_id', $publicId)->first()?->identity();
    }

    public function eligibility(string $publicId): ?Eligibility
    {
        new TrustedPrincipal($publicId);
        $user = User::withTrashed()->where('public_id', $publicId)->first();

        return $user === null ? null : $this->evaluate($user, CarbonImmutable::now('UTC'));
    }

    /** Internal credential lookup only; no credential/model crosses a public API. */
    public function forIdentifier(#[\SensitiveParameter] string $value): ?User
    {
        $mode = $this->settings->value('runa.auth', 'identifier_mode');
        try {
            if ($mode === 'email' || ($mode === 'either' && str_contains($value, '@'))) {
                return User::where('email_claim', Identifiers::email($value))->first();
            }
            if ($mode === 'login' || $mode === 'either') {
                return User::where('login_claim', Identifiers::login($value))->first();
            }
        } catch (\InvalidArgumentException) {
            return null;
        }

        throw new \RuntimeException('Auth identifier policy unavailable.');
    }

    public function evaluate(User $user, CarbonImmutable $now): Eligibility
    {
        $blockers = [];
        $restrictions = [];
        if ($user->trashed()) {
            $blockers[] = 'deleted';
        }
        if (! $user->approved) {
            $blockers[] = 'approval_required';
        }
        if ($user->activate_at !== null && $now->lt($user->activate_at)) {
            $blockers[] = 'before_activation';
        } elseif (! $user->active && ($user->activate_at === null || $now->lt($user->activate_at))) {
            $blockers[] = 'inactive';
        }
        if ($user->deactivate_at !== null && $now->gte($user->deactivate_at)) {
            $blockers[] = 'deactivated';
        }
        if ($user->expires_at !== null && $now->gte($user->expires_at)) {
            $blockers[] = 'expired';
        }
        if ($user->security_locked) {
            $blockers[] = 'security_locked';
        }
        if ($user->attempt_locked_until !== null && $now->lt($user->attempt_locked_until)) {
            $blockers[] = 'attempt_locked';
        }
        if ($user->email_verified_at === null && ($user->verification_forced || $this->settings->value('runa.auth', 'email_verification') === 'all'
            || ($this->settings->value('runa.auth', 'email_verification') === 'new' && $user->verification_required))) {
            $restrictions[] = 'email_verification_required';
        }
        if ($user->password_change_required || $this->credentials->passwordExpired($user)) {
            $restrictions[] = 'password_change_required';
        }

        return new Eligibility($user->identity(), $blockers, $restrictions, $user->security_version);
    }
}
