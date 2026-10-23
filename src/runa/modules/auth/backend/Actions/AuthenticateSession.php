<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Fortify;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Modules\Auth\Support\CredentialPolicy;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Api\PrincipalScope;

final readonly class AuthenticateSession
{
    public function __construct(private EntryTransaction $transactions, private PrepareAuthenticatedSession $prepare,
        private PrincipalScope $principals, private SessionStore $sessions, private LoginAbuse $abuse, private LookupIdentity $identities, private TrustedDevices $devices,
        private CredentialPolicy $credentials) {}

    public function login(Request $request): User
    {
        if (app('db')->connection()->transactionLevel() !== 0) {
            throw new \LogicException('Session admission requires its own root transaction.');
        }
        try {
            $user = $this->transactions->run(function (Connection $connection) use ($request): ?User {
                $this->transactions->lockAuthority($connection);
                if (! $this->abuse->source($connection, $request)) {
                    return null;
                }
                $candidate = $this->identities->forIdentifier($request->string('identifier')->toString());
                $identifier = $this->abuse->identifier($request, $candidate);
                if ($this->abuse->blocked($connection, $identifier)) {
                    return null;
                }
                $user = call_user_func(Fortify::$authenticateUsingCallback, $request);
                if (! $user instanceof User) {
                    $this->abuse->failure($connection, $identifier, $candidate);

                    return null;
                }
                $connection->table('runa_auth.users')->where('id', $user->id)->lockForUpdate()->first();
                // Guard regeneration finishes before hashing the resulting opaque session ID.
                $request->session()->invalidate();
                Auth::guard('web')->login($user, false);
                $this->prepare->handle($request, static fn () => null);
                $request->session()->regenerateToken();
                $id = $this->sessions->admit($connection, $user, $request->session()->getId(), $request->boolean('remember'));
                $request->session()->put('auth.security_version', $user->security_version);
                $request->session()->put('auth.session_id', $id);
                $this->devices->apply($connection, $user, $id, $request);
                $row = $this->sessions->current($request, $user);
                $request->session()->put('auth.remember', $row?->remembered === true);
                $this->principals->run(new TrustedPrincipal($user->public_id), fn () => $this->transactions->record($this->credentials->restrictions($user, $row) === [] ? 'login.succeeded' : 'login.credentials_accepted', $user));

                return $user;
            });
        } catch (\Throwable $error) {
            Auth::guard('web')->logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw $error;
        }
        if (! $user instanceof User) {
            $this->transactions->loginRefused();
            // Identical account/source/existence/lifecycle refusal; no lock timing oracle.
            throw new OperationRejected('invalid_credentials', 'runa.auth', 'login', 422);
        }

        return $user;
    }

    public function logout(Request $request): void
    {
        $id = $request->session()->get(Auth::guard('web')->getName());
        if (is_string($id)) {
            $this->transactions->run(function (Connection $connection) use ($id, $request): void {
                $this->transactions->lockAuthority($connection);
                $user = User::withTrashed()->where('public_id', $id)->lockForUpdate()->first();
                if ($user !== null) {
                    $connection->table('runa_auth.sessions')->where('user_id', $user->id)
                        ->where('session_hash', hash('sha256', $request->session()->getId()))->whereNull('revoked_at')
                        ->update(['revoked_at' => CarbonImmutable::now('UTC')]);
                    $this->transactions->record('logout.completed', $user);
                }
            });
        }
        Auth::guard('web')->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
