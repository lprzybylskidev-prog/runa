<?php

namespace Runa\Modules\Auth\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Runa\Contracts\Modules\HttpAccess;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Actions\SessionStore;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class AccountPolicy
{
    public function __construct(private LookupIdentity $identities, private SessionStore $sessions, private CredentialPolicy $credentials) {}

    public function principal(Request $request, ?string $module, string $operation, HttpAccess $access): ?TrustedPrincipal
    {
        // Stale cookies can still reach logout/new login and one-time public entry flows.
        $publicEntry = $module === 'runa.auth' && in_array($operation, ['csrf', 'login', 'logout', 'register', 'invitation.accept', 'verification.complete', 'password.forgot', 'password.reset'], true);
        if ($publicEntry) {
            return null;
        }
        $user = Auth::guard('web')->user();
        if (! $user instanceof User) {
            if ($access === HttpAccess::Public) {
                return null;
            }
            throw new OperationRejected('authentication_required', $module ?? 'application', $operation, 401);
        }
        $user = User::withTrashed()->where('public_id', $user->public_id)->first();
        if ($user === null) {
            throw new OperationRejected('authentication_required', $module ?? 'application', $operation, 401);
        }
        Auth::guard('web')->setUser($user);
        $eligibility = $this->identities->eligibility($user->public_id);
        if ($eligibility === null || ! $eligibility->mayAuthenticate()
            || $request->session()->get('auth.security_version') !== $eligibility->securityVersion
            || $this->sessions->current($request, $user) === null) {
            Auth::guard('web')->logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw new OperationRejected('authentication_required', $module ?? 'application', $operation, 401);
        }
        $restrictions = array_merge($eligibility->restrictions, $this->credentials->restrictions($user, $this->sessions->current($request, $user)));
        if ($restrictions !== [] && ! ($module === 'runa.auth' && $access === HttpAccess::Restricted
            && in_array($operation, ['session', 'verification.resend', 'sessions.list', 'sessions.revoke', 'session.activity', 'password.change', 'security.status', 'security.challenge', 'security.email_send', 'security.totp_begin', 'security.totp_confirm', 'security.email_enable'], true))) {
            throw new OperationRejected('account_restricted', $module ?? 'application', $operation, 403);
        }

        if ($module === 'runa.auth' && $operation === 'password.change'
            && $this->credentials->restrictions($user, $this->sessions->current($request, $user)) !== []) {
            throw new OperationRejected('account_restricted', $module, $operation, 403);
        }

        return new TrustedPrincipal($user->public_id);
    }
}
