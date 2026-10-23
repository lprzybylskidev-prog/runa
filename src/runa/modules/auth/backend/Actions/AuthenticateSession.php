<?php

namespace Runa\Modules\Auth\Actions;

use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Api\PrincipalScope;

final readonly class AuthenticateSession
{
    public function __construct(private EntryTransaction $transactions, private LoginRateLimiter $limiter, private PrepareAuthenticatedSession $prepare, private PrincipalScope $principals) {}

    public function login(Request $request): User
    {
        if (app('db')->connection()->transactionLevel() !== 0) {
            throw new \LogicException('Session admission requires its own root transaction.');
        }
        if ($this->limiter->tooManyAttempts($request)) {
            throw new OperationRejected('too_many_requests', 'runa.auth', 'login', 429, retryAfter: $this->limiter->availableIn($request));
        }
        $user = $this->transactions->run(function (Connection $connection) use ($request): ?User {
            $this->transactions->lockAuthority($connection);
            $user = call_user_func(Fortify::$authenticateUsingCallback, $request);
            if ($user instanceof User) {
                // Registry/advisory locks serialize lifecycle changes until admission commits.
                $this->principals->run(new TrustedPrincipal($user->public_id), fn () => $this->transactions->record('login.succeeded', $user));
            }

            return $user;
        });
        if (! $user instanceof User) {
            $this->limiter->increment($request);
            $this->transactions->loginRefused();
            throw new OperationRejected('invalid_credentials', 'runa.auth', 'login', 422);
        }
        // No Failed event containing raw credentials; no remember token before its policy flow.
        $request->session()->invalidate();
        Auth::guard('web')->login($user, false);
        $this->prepare->handle($request, static fn () => null);
        $request->session()->regenerateToken();
        $request->session()->put('auth.security_version', $user->security_version);

        return $user;
    }

    public function logout(Request $request): void
    {
        $id = $request->session()->get(Auth::guard('web')->getName());
        if (is_string($id)) {
            $this->transactions->run(function (Connection $connection) use ($id): void {
                $this->transactions->lockAuthority($connection);
                $user = User::withTrashed()->where('public_id', $id)->lockForUpdate()->first();
                if ($user !== null) {
                    $this->transactions->record('logout.completed', $user);
                }
            });
        }
        // logoutCurrentDevice avoids rotating a remember token through an unaudited model save.
        Auth::guard('web')->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
