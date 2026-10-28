<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;

/** Fortify's custom credential callback; lifecycle status never reaches anonymous callers. */
final readonly class AuthenticateCredentials
{
    public function __construct(private LookupIdentity $identities) {}

    public function user(Request $request): ?User
    {
        $user = $this->identities->forIdentifier($request->string('identifier')->toString());
        // Equal expensive work for absent/passwordless and existing accounts.
        $dummy = Hash::make(bin2hex(random_bytes(32)));
        $valid = Hash::check($request->string('password')->toString(), $user->password ?? $dummy);
        if (! $valid || $user === null || ! $this->identities->evaluate($user, CarbonImmutable::now('UTC'))->mayAuthenticate()) {
            return null;
        }

        return $user;
    }
}
