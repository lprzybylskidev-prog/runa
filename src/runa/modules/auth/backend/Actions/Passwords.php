<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Modules\Auth\Support\CredentialPolicy;
use Runa\Modules\Auth\Support\EntryPassword;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Modules\Auth\Support\SecurityContext;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class Passwords
{
    public function __construct(private SecurityContext $context, private CredentialFactors $factors,
        private CredentialPolicy $policy, private EntryTransaction $transactions, private EntryTokens $tokens,
        private LookupIdentity $identities, private SessionStore $sessions) {}

    public function requestReset(#[\SensitiveParameter] string $identifier): void
    {
        $this->transactions->run(function (Connection $connection) use ($identifier): void {
            $this->transactions->lockAuthority($connection);
            $candidate = $this->identities->forIdentifier($identifier);
            $user = $candidate === null ? null : User::where('id', $candidate->id)->lockForUpdate()->first();
            if ($user !== null && $user->password !== null) {
                $this->tokens->issueLocked($connection, $user, 'reset');
            }
        });
    }

    public function change(Request $request): array
    {
        $result = $this->context->run($request, function (Connection $connection, User $user, object $session) use ($request): ?array {
            if (! Hash::check($request->string('current_password')->toString(), $user->password ?? '')) {
                return null;
            }
            // Mandatory login 2FA must be completed first. Optional change-specific 2FA is a fresh proof.
            if ($this->policy->restrictions($user, $session) !== []) {
                return null;
            }
            if ($this->policy->value('password_change_factor')
                && ! $this->factors->verify($connection, $user, $session, $request->string('method')->toString(), $request->string('code')->toString())) {
                return null;
            }
            $this->replaceLocked($connection, $user, $request->string('password')->toString());
            $choice = $request->input('sessions', $this->policy->value('password_session_policy'));
            $query = $connection->table('runa_auth.sessions')->where('user_id', $user->id)->whereNull('revoked_at');
            if ($choice === 'revoke_others') {
                $query->where('id', '<>', $session->id);
            }
            if ($choice !== 'keep_all') {
                $query->update(['revoked_at' => now()]);
            }
            $this->transactions->record('password.changed', $user);

            return ['updated' => true, 'reauthenticate' => $choice === 'revoke_all'];
        });

        return $result;
    }

    /** Internal only. Current user lock and default transaction are required. */
    public function replaceLocked(Connection $connection, User $user, #[\SensitiveParameter] string $password): void
    {
        if ($connection !== app('db')->connection() || $connection->transactionLevel() < 1) {
            throw new \LogicException('Password mutation requires the default transaction.');
        }
        Validator::make(['password' => $password, 'password_confirmation' => $password], ['password' => EntryPassword::rules()])->validate();
        $history = $connection->table('runa_auth.password_history')->where('user_id', $user->id)->orderByDesc('id')
            ->limit($this->policy->value('password_history'))->pluck('password_hash')->all();
        if ($user->password !== null) {
            $history[] = $user->password;
        }
        foreach ($history as $hash) {
            if (Hash::check($password, $hash)) {
                throw new OperationRejected('password_reused', 'runa.auth', 'password.change', 422);
            }
        }
        if ($user->password !== null) {
            $connection->table('runa_auth.password_history')->insert(['user_id' => $user->id, 'password_hash' => $user->password, 'created_at' => now()]);
        }
        $retained = $connection->table('runa_auth.password_history')->where('user_id', $user->id)->orderByDesc('id')
            ->limit($this->policy->value('password_history'))->pluck('id');
        $connection->table('runa_auth.password_history')->where('user_id', $user->id)->whereNotIn('id', $retained)->delete();
        $user->password = Hash::make($password);
        $user->password_changed_at = CarbonImmutable::now('UTC');
        $user->password_change_required = false;
        $user->remember_token = null;
        $user->version++;
        $user->save();
        $connection->table('runa_auth.trusted_devices')->where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
        $connection->table('runa_auth.step_up_proofs')->where('user_id', $user->id)->delete();
        $connection->table('runa_auth.entry_tokens')->where('user_id', $user->id)->whereNull('consumed_at')->whereNull('invalidated_at')
            ->update(['invalidated_at' => now(), 'delivery_secret' => null]);
    }

    public function reset(#[\SensitiveParameter] string $secret, #[\SensitiveParameter] string $password): void
    {
        $this->transactions->run(function (Connection $connection) use ($secret, $password): void {
            $this->transactions->lockAuthority($connection);
            $candidate = $connection->table('runa_auth.entry_tokens')->where('token_hash', hash('sha256', $secret))->where('purpose', 'reset')->first();
            $user = $candidate === null ? null : User::where('id', $candidate->user_id)->lockForUpdate()->first();
            $token = $candidate === null ? null : $connection->table('runa_auth.entry_tokens')->where('id', $candidate->id)->lockForUpdate()->first();
            if ($user === null || $token === null || $token->consumed_at !== null || $token->invalidated_at !== null
                || CarbonImmutable::parse($token->expires_at)->lte(CarbonImmutable::now('UTC')) || $token->recipient !== $user->email
                || $token->security_version !== $user->security_version || ! $this->identities->evaluate($user, CarbonImmutable::now('UTC'))->mayAuthenticate()) {
                throw new OperationRejected('entry_token_invalid', 'runa.auth', 'password.reset', 422);
            }
            $this->replaceLocked($connection, $user, $password);
            $user->security_version++;
            $user->save();
            $this->sessions->revokeUser($connection, $user);
            $connection->table('runa_auth.entry_tokens')->where('id', $token->id)->update(['consumed_at' => now()]);
            $this->transactions->record('password.reset', $user);
            // No login or email-verification/factor mutation: next login still requires enrolled/mandatory 2FA.
        });
    }
}
