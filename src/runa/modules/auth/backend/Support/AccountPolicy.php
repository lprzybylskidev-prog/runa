<?php

namespace Runa\Modules\Auth\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Runa\Contracts\Modules\HttpAccess;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Modules\Auth\Actions\SessionStore;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class AccountPolicy
{
    public function __construct(private LookupIdentity $identities, private SessionStore $sessions, private CredentialPolicy $credentials) {}

    public function principal(Request $request, ?string $module, string $operation, HttpAccess $access): ?TrustedPrincipal
    {
        // One synchronous admission holds the settings registry lock. The owning
        // operation runs afterwards and performs its own current authorization.
        return DB::connection()->transaction(fn (): ?TrustedPrincipal => $this->admit($request, $module, $operation, $access));
    }

    private function admit(Request $request, ?string $module, string $operation, HttpAccess $access): ?TrustedPrincipal
    {
        // Stale cookies can still reach logout/new login and one-time public entry flows.
        $publicEntry = $module === 'runa.auth' && in_array($operation, ['entry.options', 'csrf', 'login', 'logout', 'register', 'invitation.accept', 'verification.complete', 'password.forgot', 'password.reset', 'email.complete'], true);
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
        // Anchor the registry lock at admission level before child read scopes.
        app(ModuleRegistry::class)->snapshot();
        $user = User::withTrashed()->where('public_id', $user->public_id)->first();
        if ($user === null) {
            throw new OperationRejected('authentication_required', $module ?? 'application', $operation, 401);
        }
        Auth::guard('web')->setUser($user);
        $eligibility = $this->identities->evaluate($user, CarbonImmutable::now('UTC'));
        $session = $this->sessions->current($request, $user);
        if (! $eligibility->mayAuthenticate()
            || $request->session()->get('auth.security_version') !== $eligibility->securityVersion
            || $session === null) {
            Auth::guard('web')->logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw new OperationRejected('authentication_required', $module ?? 'application', $operation, 401);
        }
        $credentialRestrictions = $this->credentials->restrictions($user, $session);
        $restrictions = array_merge($eligibility->restrictions, $credentialRestrictions);
        if ($restrictions !== [] && ! ($module === 'runa.auth' && $access === HttpAccess::Restricted
            && in_array($operation, ['session', 'verification.resend', 'sessions.list', 'sessions.revoke', 'session.activity', 'password.change', 'security.status', 'security.challenge', 'security.email_send', 'security.totp_begin', 'security.totp_confirm', 'security.email_enable'], true))) {
            throw new OperationRejected('account_restricted', $module ?? 'application', $operation, 403);
        }

        if ($module === 'runa.auth' && $operation === 'password.change'
            && $credentialRestrictions !== []) {
            throw new OperationRejected('account_restricted', $module, $operation, 403);
        }

        return new TrustedPrincipal($user->public_id);
    }
}
