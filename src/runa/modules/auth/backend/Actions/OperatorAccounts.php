<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\SettingsActor;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Contracts\AuditRecords;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Modules\Auth\Contracts\OperatorAuthority;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\Settings\ModuleSettings;

/** Trusted local installer/CLI entrypoint. No public issuance endpoint or module API. */
final readonly class OperatorAccounts
{
    public function __construct(private EntryTransaction $transactions, private SessionStore $sessions,
        private LookupIdentity $identities, private ModuleRegistry $registry) {}

    /** Secret result belongs only to the private operator handoff, never command output. */
    public function initialize(NewIdentity $input): array
    {
        return $this->transactions->run(function (Connection $connection) use ($input): array {
            $this->ready($connection);
            $authority = $this->authority();
            $state = $connection->table('runa_auth.initialization')->where('id', 1)->lockForUpdate()->firstOrFail();
            if ($state->completed_at !== null) {
                $user = User::where('public_id', $state->subject)->lockForUpdate()->firstOrFail();
                if ($user->email !== $input->email || ! $authority->isAdministrator($connection, $user->public_id)
                    || $user->password !== null) {
                    throw new \RuntimeException('Administrator initialization is already complete.');
                }

                return $this->issueLocked($connection, $user, 'initialize');
            }
            if (User::withTrashed()->where('email_claim', $input->email)->exists()) {
                throw new \RuntimeException('Initialization requires a new identity.');
            }
            if (app(ModuleSettings::class)->value('runa.auth', 'identifier_mode') === 'login' && $input->login === null) {
                throw new \InvalidArgumentException('A login is required by current policy.');
            }
            $user = new User;
            $user->forceFill(['name' => $input->name, 'first_name' => $input->firstName, 'surname' => $input->surname,
                'email' => $input->email, 'email_claim' => $input->email, 'login' => $input->login, 'login_claim' => $input->login,
                'active' => true, 'approved' => true, 'verification_required' => true, 'version' => 1, 'security_version' => 1]);
            app(EmailAddresses::class)->available($connection, $user, $input->email, true);
            $user->save();
            $authority->initialize($connection, $user->public_id);
            $connection->table('runa_auth.initialization')->where('id', 1)->update(['subject' => $user->public_id, 'completed_at' => now()]);
            $this->transactions->record('operator.initialized', $user);

            return $this->issueLocked($connection, $user, 'initialize');
        });
    }

    public function recover(string $publicId): array
    {
        new TrustedPrincipal($publicId);

        return $this->transactions->run(function (Connection $connection) use ($publicId): array {
            $this->ready($connection);
            $user = User::where('public_id', $publicId)->lockForUpdate()->firstOrFail();
            if (! $this->authority()->isAdministrator($connection, $publicId)) {
                throw new \RuntimeException('Recovery requires an existing administrator.');
            }
            // Explicit trusted operator recovery; never restores a deleted identity.
            $user->active = true;
            $user->approved = true;
            $user->activate_at = null;
            $user->deactivate_at = null;
            $user->expires_at = null;
            $user->security_locked = false;
            $user->attempt_locked_until = null;
            $user->totp_secret = null;
            $user->totp_pending = null;
            $user->totp_pending_until = null;
            $user->totp_counter = null;
            $user->recovery_hashes = [];
            $user->email_factor = false;
            $user->password_change_required = true;
            $user->operator_recovery_pending = true;
            $user->security_version++;
            $user->version++;
            $user->remember_token = null;
            $user->save();
            $this->sessions->revokeUser($connection, $user);
            $connection->table('runa_auth.step_up_proofs')->where('user_id', $user->id)->delete();
            $connection->table('runa_auth.entry_tokens')->where('user_id', $user->id)->whereNull('consumed_at')->whereNull('invalidated_at')
                ->update(['invalidated_at' => now(), 'delivery_secret' => null]);
            $this->transactions->record('operator.recovery_issued', $user);

            return $this->issueLocked($connection, $user, 'recover');
        });
    }

    /** Returns false only when this is an ordinary password-reset token. */
    public function redeem(#[\SensitiveParameter] string $secret, #[\SensitiveParameter] string $password): bool
    {
        return $this->transactions->run(function (Connection $connection) use ($secret, $password): bool {
            $hash = hash('sha256', $secret);
            $candidate = $connection->table('runa_auth.operator_tokens')->where('token_hash', $hash)->first();
            if ($candidate === null) {
                return false;
            }
            $this->ready($connection);
            $user = User::where('id', $candidate->user_id)->lockForUpdate()->first();
            $token = $connection->table('runa_auth.operator_tokens')->where('id', $candidate->id)->lockForUpdate()->first();
            if ($user === null || $token->consumed_at !== null || CarbonImmutable::parse($token->expires_at)->lte(CarbonImmutable::now('UTC'))
                || $token->security_version !== $user->security_version
                || ! $this->authority()->isAdministrator($connection, $user->public_id)
                || array_diff($this->identities->evaluate($user, CarbonImmutable::now('UTC'))->blockers, ['operator_recovery_required']) !== []) {
                throw new OperationRejected('entry_token_invalid', 'runa.auth', 'password.reset', 422);
            }
            $user->operator_recovery_pending = false;
            if ($token->purpose === 'initialize') {
                $user->email_verified_at = CarbonImmutable::now('UTC');
            }
            app(Passwords::class)->replaceLocked($connection, $user, $password);
            $user->security_version++;
            $user->save();
            $this->sessions->revokeUser($connection, $user);
            $connection->table('runa_auth.operator_tokens')->where('user_id', $user->id)->whereNull('consumed_at')->update(['consumed_at' => now()]);
            $this->transactions->record('operator.recovery_completed', $user);

            // No session, verification or second-factor proof is issued. Normal login enforces policy.
            return true;
        });
    }

    public function refused(): void
    {
        app(ModuleApis::class)->resolve('runa.audit', AuditRecords::class, '1.0.0')
            ->attempt(new AuditEvent('runa.auth', 'operator.refused', 'refused'));
    }

    public function restorePolicy(int $revision): void
    {
        if (! config('auth.operator_recovery_enabled', true)) {
            throw new \RuntimeException('Operator recovery is disabled.');
        }
        $settings = app(ModuleSettings::class);
        $state = $settings->read('runa.auth');
        $result = $settings->update('runa.auth', app(Policies::class)->restoreValues($revision), $state['revision'], $state['generation'], new SettingsActor('system', 'operator.cli'));
        if ($result->status !== 'succeeded') {
            throw new \RuntimeException('Operator policy restoration did not complete.');
        }
    }

    private function ready(Connection $connection): void
    {
        if (! config('auth.operator_recovery_enabled', true)) {
            throw new \RuntimeException('Operator recovery is disabled.');
        }
        $snapshot = $this->registry->snapshot();
        $record = $snapshot->modules['runa.auth'] ?? null;
        $audit = $snapshot->modules['runa.audit'] ?? null;
        if ($record === null || $audit === null || ! $record->registered || ! $record->sourcePresent || ! $record->enabled
            || $record->installedVersion !== $record->sourceVersion || ! $audit->sourcePresent || ! $audit->enabled
            || $snapshot->fenced !== []) {
            throw new \RuntimeException('Complete module initialization/recovery before operator account changes.');
        }
        $this->transactions->lockAuthority($connection);
    }

    private function authority(): OperatorAuthority
    {
        if (! app()->bound(OperatorAuthority::class)) {
            throw new \RuntimeException('Permissions operator authority is not integrated yet.');
        }

        return app(OperatorAuthority::class);
    }

    private function issueLocked(Connection $connection, User $user, string $purpose): array
    {
        $secret = bin2hex(random_bytes(32));
        $connection->table('runa_auth.operator_tokens')->where('user_id', $user->id)->whereNull('consumed_at')->update(['consumed_at' => now()]);
        $connection->table('runa_auth.operator_tokens')->insert([
            'public_id' => (string) Str::uuid7(), 'user_id' => $user->id, 'token_hash' => hash('sha256', $secret),
            'purpose' => $purpose, 'security_version' => $user->security_version, 'expires_at' => now()->addMinutes(15), 'created_at' => now(),
        ]);

        return ['public_id' => $user->public_id, 'token' => $secret, 'expires_in_seconds' => 900];
    }
}
