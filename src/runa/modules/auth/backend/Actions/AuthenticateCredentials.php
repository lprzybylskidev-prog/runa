<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;

/** Lifecycle feedback is available only after successful credential verification. */
final readonly class AuthenticateCredentials
{
    public function __construct(private LookupIdentity $identities) {}

    public function user(Request $request): ?User
    {
        $request->attributes->remove('runa.auth.login_inactive');
        $user = $this->identities->forIdentifier($request->string('identifier')->toString());
        // Equal expensive work for absent/passwordless and existing accounts.
        $dummy = Hash::make(bin2hex(random_bytes(32)));
        $valid = Hash::check($request->string('password')->toString(), $user->password ?? $dummy);
        if (! $valid || $user === null) {
            return null;
        }

        $state = $this->identities->evaluate($user, CarbonImmutable::now('UTC'));
        if (! $state->mayAuthenticate()) {
            if (array_diff($state->blockers, ['inactive', 'before_activation', 'deactivated']) === []) {
                $request->attributes->set('runa.auth.login_inactive', true);
            }

            return null;
        }

        return $user;
    }
}
