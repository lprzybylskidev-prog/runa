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

final readonly class StepUp implements AuthStepUp
{
    public function __construct(private SecurityContext $context, private CredentialPolicy $policy,
        private LookupIdentity $identities, private CredentialFactors $factors, private EntryTransaction $transactions, private PrincipalScope $principals) {}

    private function validate(string $action, string $requirement): void
    {
        if (! preg_match('/\A[a-z][a-z0-9_.:-]{0,127}\z/D', $action)
            || ! in_array($requirement, ['password', 'second_factor', 'both'], true)) {
            throw new \InvalidArgumentException('Invalid step-up requirement.');
        }
    }

    public function confirm(Request $request): array
    {
        $action = $request->string('action')->toString();
        $requirement = $request->string('requirement')->toString();
        $this->validate($action, $requirement);

        return $this->context->run($request, function (Connection $connection, User $user, object $session) use ($request, $action, $requirement): ?array {
            // Restricted sessions must first finish login factors and password/verification flows.
            if ($this->policy->restrictions($user, $session) !== [] || $user->password_change_required || $this->policy->passwordExpired($user)
                || ! $this->identities->evaluate($user, CarbonImmutable::now('UTC'))->mayOperate()) {
                return null;
            }
            if (in_array($requirement, ['password', 'both'], true) && ! Hash::check($request->string('current_password')->toString(), $user->password ?? '')) {
                return null;
            }
            if (in_array($requirement, ['second_factor', 'both'], true)
                && ! $this->factors->verify($connection, $user, $session, $request->string('method')->toString(), $request->string('code')->toString())) {
                return null;
            }
            $connection->table('runa_auth.step_up_proofs')->where('user_id', $user->id)->where('session_hash', $session->session_hash)->where('action', $action)->delete();
            $connection->table('runa_auth.step_up_proofs')->insert(['user_id' => $user->id, 'session_hash' => $session->session_hash,
                'security_version' => $user->security_version, 'action' => $action, 'requirement' => $requirement,
                'factor_method' => $requirement === 'password' ? null : $request->string('method')->toString(),
                'verified_at' => now(), 'expires_at' => now()->addSeconds($this->policy->value('step_up_seconds'))]);
            $this->transactions->record('step_up.confirmed', $user);

            return ['confirmed' => true];
        });
    }

    public function assertVerified(string $action, string $requirement, int $ttlSeconds = 300, bool $everyAction = false): bool
    {
        $this->validate($action, $requirement);
        if ($ttlSeconds < 1 || $ttlSeconds > 900 || app('db')->connection()->transactionLevel() < 1) {
            throw new \LogicException('Step-up requires a bounded TTL and the owning business transaction.');
        }

        return $this->context->run(app('request'), function (Connection $connection, User $user, object $session) use ($action, $requirement, $ttlSeconds, $everyAction): bool {
            if ($this->principals->current()?->publicId !== $user->public_id || $this->policy->restrictions($user, $session) !== []
                || ! $this->identities->evaluate($user, CarbonImmutable::now('UTC'))->mayOperate() || $user->password_change_required || $this->policy->passwordExpired($user)) {
                throw new OperationRejected('step_up_required', 'runa.auth', 'step_up', 403);
            }
            $proof = $connection->table('runa_auth.step_up_proofs')->where('user_id', $user->id)->where('session_hash', $session->session_hash)
                ->where('security_version', $user->security_version)->where('action', $action)->whereNull('consumed_at')
                ->whereIn('requirement', $requirement === 'both' ? ['both'] : [$requirement, 'both'])
                ->where('expires_at', '>', now())->where('verified_at', '>', CarbonImmutable::now('UTC')->subSeconds(min($ttlSeconds, $this->policy->value('step_up_seconds'))))
                ->lockForUpdate()->first();
            if ($proof === null || ($proof->requirement !== 'password' && ! $this->policy->accepts($proof->factor_method ?? ''))) {
                throw new OperationRejected('step_up_required', 'runa.auth', 'step_up', 403);
            }
            if ($everyAction) {
                $connection->table('runa_auth.step_up_proofs')->where('id', $proof->id)->update(['consumed_at' => now()]);
                $this->transactions->record('step_up.consumed', $user);
            }

            return true;
        }, count: false);
    }
}
