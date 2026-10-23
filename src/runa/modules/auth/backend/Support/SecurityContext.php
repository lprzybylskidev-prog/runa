<?php

namespace Runa\Modules\Auth\Support;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Runa\Modules\Auth\Actions\SessionStore;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Support\Modules\Api\OperationRejected;

/** Recheck security-sensitive self service under registry -> authority -> user locks. */
final readonly class SecurityContext
{
    public function __construct(private EntryTransaction $transactions, private SessionStore $sessions,
        private LookupIdentity $identities, private CredentialPolicy $policy) {}

    public function run(Request $request, Closure $work, bool $count = true): mixed
    {
        $result = $this->transactions->run(function (Connection $connection) use ($request, $work, $count): mixed {
            $this->transactions->lockAuthority($connection);
            $subject = Auth::guard('web')->user();
            $user = $subject instanceof User ? User::where('id', $subject->id)->lockForUpdate()->first() : null;
            $row = $user === null ? null : $this->sessions->current($request, $user);
            if ($user === null || $row === null || ! $this->identities->evaluate($user, CarbonImmutable::now('UTC'))->mayAuthenticate()) {
                throw new OperationRejected('authentication_required', 'runa.auth', 'security', 401);
            }
            if ($count && ! $this->attempt($connection, $user)) {
                return null;
            }

            return $work($connection, $user, $row);
        });
        if ($result === null) {
            $this->transactions->securityRefused();
            throw new OperationRejected('security_proof_invalid', 'runa.auth', 'security', 422);
        }

        return $result;
    }

    private function attempt(Connection $connection, User $user): bool
    {
        $fingerprint = hash_hmac('sha256', 'security:'.$user->public_id, config('app.key'));
        $query = $connection->table('runa_auth.login_windows')->where('fingerprint', $fingerprint);
        $row = $query->first();
        if ($row !== null && CarbonImmutable::parse($row->expires_at)->isFuture()) {
            if ($row->attempts >= $this->policy->value('security_attempt_limit')) {
                return false;
            }
            $query->increment('attempts');
        } else {
            $connection->table('runa_auth.login_windows')->updateOrInsert(['fingerprint' => $fingerprint],
                ['attempts' => 1, 'expires_at' => CarbonImmutable::now('UTC')->addMinutes(5), 'locked_until' => null]);
        }

        return true;
    }
}
