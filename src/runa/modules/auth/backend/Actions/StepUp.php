<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\Modules\Auth\Contracts\AuthStepUp;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Modules\Auth\Support\CredentialPolicy;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Modules\Auth\Support\SecurityContext;
use Runa\Support\Modules\Api\OperationRejected;

/** @phpstan-import-type SessionRow from SessionStore */
final readonly class StepUp implements AuthStepUp
{
    public function __construct(private SecurityContext $context, private CredentialPolicy $policy,
        private LookupIdentity $identities, private EntryTransaction $transactions, private PrincipalScope $principals) {}

    private function validate(string $action): void
    {
        if (! preg_match('/\A[a-z][a-z0-9_.:-]{0,127}\z/D', $action)) {
            throw new \InvalidArgumentException('Invalid step-up requirement.');
        }
    }

    /**
     * @return array{confirmed: bool}
     */
    public function confirm(Request $request): array
    {
        $action = $request->string('action')->toString();
        $this->validate($action);

        return $this->context->run($request, /** @param SessionRow $session */ function (Connection $connection, User $user, object $session) use ($request, $action): ?array {
            // Restricted sessions must first finish login factors and password/verification flows.
            if ($this->policy->restrictions($user, $session) !== [] || $user->password_change_required || $this->policy->passwordExpired($user)
                || ! $this->identities->evaluate($user, CarbonImmutable::now('UTC'))->mayOperate()) {
                return null;
            }
            if (! Hash::check($request->string('current_password')->toString(), $user->password ?? '')) {
                return null;
            }
            $connection->table('runa_auth.step_up_proofs')->where('user_id', $user->id)->where('session_hash', $session->session_hash)->where('action', $action)->delete();
            $connection->table('runa_auth.step_up_proofs')->insert(['user_id' => $user->id, 'session_hash' => $session->session_hash,
                'security_version' => $user->security_version, 'action' => $action,
                'verified_at' => now(), 'expires_at' => now()->addSeconds($this->policy->integer('step_up_seconds'))]);
            $this->transactions->record('step_up.confirmed', $user);

            return ['confirmed' => true];
        });
    }

    /** @return array{remaining_seconds: float, heartbeat_seconds: float} */
    public function activity(string $action, int $ttlSeconds = 300, bool $renew = false): array
    {
        return $this->verify($action, $ttlSeconds, false, true, $renew);
    }

    public function assertActive(string $action, int $ttlSeconds = 300): bool
    {
        $this->activity($action, $ttlSeconds);

        return true;
    }

    public function assertVerified(string $action, int $ttlSeconds = 300, bool $everyAction = false): bool
    {
        $this->verify($action, $ttlSeconds, $everyAction, false, false);

        return true;
    }

    /** @return array{remaining_seconds: float, heartbeat_seconds: float} */
    private function verify(string $action, int $ttlSeconds, bool $everyAction, bool $idle, bool $renew): array
    {
        $this->validate($action);
        if ($ttlSeconds < 1 || $ttlSeconds > 900 || app('db')->connection()->transactionLevel() < 1) {
            throw new \LogicException('Step-up requires a bounded TTL and the owning business transaction.');
        }

        return $this->context->run(app('request'), /** @param SessionRow $session */ function (Connection $connection, User $user, object $session) use ($action, $ttlSeconds, $everyAction, $idle, $renew): array {
            if ($this->principals->current()?->publicId !== $user->public_id || $this->policy->restrictions($user, $session) !== []
                || ! $this->identities->evaluate($user, CarbonImmutable::now('UTC'))->mayOperate() || $user->password_change_required || $this->policy->passwordExpired($user)) {
                throw new OperationRejected('step_up_required', 'runa.auth', 'step_up', 403);
            }
            /** @var (\stdClass&object{id: int, verified_at: string, expires_at: string, activity_at: string|null, idle_expires_at: string|null})|null $proof */
            $proof = $connection->table('runa_auth.step_up_proofs')->where('user_id', $user->id)->where('session_hash', $session->session_hash)
                ->where('security_version', $user->security_version)->where('action', $action)->whereNull('consumed_at')
                ->lockForUpdate()->first();
            if ($proof === null) {
                throw new OperationRejected('step_up_required', 'runa.auth', 'step_up', 403);
            }
            $now = CarbonImmutable::now('UTC');
            $verified = CarbonImmutable::parse($proof->verified_at);
            $captured = CarbonImmutable::parse($proof->expires_at);
            $ttl = min($ttlSeconds, $this->policy->integer('step_up_seconds'), $verified->diffInSeconds($captured));
            $last = $idle && $proof->activity_at !== null ? CarbonImmutable::parse($proof->activity_at) : $verified;
            $expiry = $idle && $proof->idle_expires_at !== null ? CarbonImmutable::parse($proof->idle_expires_at) : $captured;
            $deadline = $expiry->min($last->addSeconds($ttl));
            if ($deadline->lessThanOrEqualTo($now)) {
                throw new OperationRejected('step_up_required', 'runa.auth', 'step_up', 403);
            }
            if ($renew) {
                $deadline = $now->addSeconds($ttl);
                $connection->table('runa_auth.step_up_proofs')->where('id', $proof->id)->update([
                    'activity_at' => $now, 'idle_expires_at' => $deadline,
                ]);
            }
            if ($everyAction) {
                $connection->table('runa_auth.step_up_proofs')->where('id', $proof->id)->update(['consumed_at' => $now]);
                $this->transactions->record('step_up.consumed', $user);
            }

            return ['remaining_seconds' => (float) $now->diffInSeconds($deadline), 'heartbeat_seconds' => (float) min(30, $ttl / 3)];
        }, count: false);
    }
}
