<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\AuthorityGuard;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Contracts\AuthInvitations;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Settings\ModuleSettings;
use Runa\Support\Modules\Work\ModuleWork;

final readonly class EntryTokens implements AuthInvitations
{
    public function __construct(private EntryTransaction $transactions, private LookupIdentity $identities,
        private ModuleSettings $settings, private ModuleWork $work, private AuthorityGuard $authority) {}

    public function invite(string $publicId, int $expectedVersion): bool
    {
        new TrustedPrincipal($publicId);

        return $this->transactions->run(function (Connection $connection) use ($publicId, $expectedVersion): bool {
            $this->transactions->lockAuthority($connection);
            $user = User::where('public_id', $publicId)->lockForUpdate()->first();
            if ($user === null || $user->version !== $expectedVersion || $user->password !== null) {
                return false;
            }
            $this->authority->assertMayRemove($connection, [$publicId]);

            return $this->issueLocked($connection, $user, 'invitation');
        });
    }

    public function invalidate(string $publicId, int $expectedVersion): bool
    {
        new TrustedPrincipal($publicId);

        return $this->transactions->run(function (Connection $connection) use ($publicId, $expectedVersion): bool {
            $this->transactions->lockAuthority($connection);
            $user = User::where('public_id', $publicId)->lockForUpdate()->first();
            if ($user === null || $user->version !== $expectedVersion) {
                return false;
            }
            $this->authority->assertMayRemove($connection, [$publicId]);
            $changed = $connection->table('runa_auth.entry_tokens')->where('user_id', $user->id)->where('purpose', 'invitation')
                ->whereNull('consumed_at')->whereNull('invalidated_at')->update(['invalidated_at' => now(), 'delivery_secret' => null]);
            if ($changed) {
                $this->transactions->record('invitation.invalidated', $user);
            }

            return $changed > 0;
        });
    }

    /** Internal only: owning registry/authority/user locks are already held. */
    public function issueLocked(Connection $connection, User $user, string $purpose, int $reminders = 0, ?string $sessionHash = null): bool
    {
        if ($connection !== app('db')->connection() || $connection->transactionLevel() < 1
            || ! in_array($purpose, ['invitation', 'verification', 'reset', 'email_otp'], true)) {
            throw new \LogicException('Invalid Auth token transaction.');
        }
        if (! $this->identities->evaluate($user, CarbonImmutable::now('UTC'))->mayAuthenticate()
            || ($purpose === 'invitation' && $user->password !== null)
            || ($purpose === 'verification' && $user->email_verified_at !== null)
            || ($purpose === 'reset' && $user->password === null)
            || ($purpose === 'email_otp' && (! $user->email_factor || $user->email_verified_at === null || $sessionHash === null))) {
            return false;
        }
        $minutes = $this->settings->value('runa.auth', $purpose.'_minutes');
        $interval = $this->settings->value('runa.auth', 'invitation_reminder_minutes');
        $id = (string) Str::uuid7();
        $secret = $purpose === 'email_otp' ? str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT) : bin2hex(random_bytes(32));
        $enqueue = $this->work->prepareDispatch('runa.auth', 'entry.deliver', ['token_id' => $id]);
        $connection->table('runa_auth.entry_tokens')->where('user_id', $user->id)->where('purpose', $purpose)
            ->whereNull('consumed_at')->whereNull('invalidated_at')->update(['invalidated_at' => now(), 'delivery_secret' => null]);
        $connection->table('runa_auth.entry_tokens')->insert([
            'public_id' => $id, 'user_id' => $user->id, 'purpose' => $purpose, 'token_hash' => $purpose === 'email_otp' ? hash_hmac('sha256', $id.':'.$secret, config('app.key')) : hash('sha256', $secret),
            'session_hash' => $sessionHash,
            'recipient' => $user->email, 'delivery_secret' => Crypt::encryptString($secret), 'security_version' => $user->security_version,
            'expires_at' => now()->addMinutes($minutes), 'reminders' => $reminders,
            'remind_at' => $purpose === 'invitation' && $interval > 0 ? now()->addMinutes($interval) : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $enqueue(); // Work and envelope commit with token+Audit; workers cannot read an uncommitted token.
        $this->transactions->record($purpose.'.issued', $user);

        return true;
    }

    public function resendVerification(string $publicId): void
    {
        $this->transactions->run(function (Connection $connection) use ($publicId): void {
            $this->transactions->lockAuthority($connection);
            $user = User::where('public_id', $publicId)->lockForUpdate()->first();
            if ($user !== null) {
                $this->issueLocked($connection, $user, 'verification');
            }
        });
    }

    /** Tokens are submitted in POST bodies, never server query strings. No session is issued here. */
    public function consume(#[\SensitiveParameter] string $secret, string $purpose, #[\SensitiveParameter] ?string $password = null): void
    {
        $hash = hash('sha256', $secret);
        if (! in_array($purpose, ['invitation', 'verification'], true)) {
            throw new \InvalidArgumentException('Invalid entry purpose.');
        }
        $this->transactions->run(function (Connection $connection) use ($hash, $purpose, $password): void {
            $this->transactions->lockAuthority($connection);
            $candidate = $connection->table('runa_auth.entry_tokens')->where('token_hash', $hash)->where('purpose', $purpose)->first();
            $user = $candidate === null ? null : User::where('id', $candidate->user_id)->lockForUpdate()->first();
            $token = $candidate === null ? null : $connection->table('runa_auth.entry_tokens')->where('id', $candidate->id)->lockForUpdate()->first();
            if ($user === null || $token === null || $token->consumed_at !== null || $token->invalidated_at !== null
                || CarbonImmutable::parse($token->expires_at)->lte(CarbonImmutable::now('UTC'))
                || $token->recipient !== $user->email || $token->security_version !== $user->security_version
                || ! $this->identities->evaluate($user, CarbonImmutable::now('UTC'))->mayAuthenticate()
                || ($purpose === 'invitation' && ($user->password !== null || $password === null))) {
                throw new OperationRejected('entry_token_invalid', 'runa.auth', 'entry.complete', 422);
            }
            if ($purpose === 'invitation') {
                app(Passwords::class)->replaceLocked($connection, $user, $password);
                $user->security_version++;
                $user->remember_token = null;
            }
            $user->email_verified_at = CarbonImmutable::now('UTC');
            $user->verification_forced = false;
            if ($purpose !== 'invitation') {
                $user->version++;
            }
            $user->save();
            $connection->table('runa_auth.entry_tokens')->where('id', $token->id)->update(['consumed_at' => now(), 'delivery_secret' => null]);
            $connection->table('runa_auth.entry_tokens')->where('user_id', $user->id)->where('id', '<>', $token->id)
                ->whereNull('invalidated_at')->whereNull('consumed_at')->update(['invalidated_at' => now(), 'delivery_secret' => null]);
            $this->transactions->record($purpose.'.completed', $user);
        });
    }

    public function remind(): void
    {
        // Bounded candidates; each transaction rechecks the current row and policy.
        $ids = app('db')->connection()->table('runa_auth.entry_tokens')->where('purpose', 'invitation')
            ->whereNull('consumed_at')->whereNull('invalidated_at')->whereNotNull('sent_at')->where('remind_at', '<=', now())
            ->orderBy('id')->limit(100)->pluck('id');
        foreach ($ids as $id) {
            $this->transactions->run(function (Connection $connection) use ($id): void {
                $limit = $this->settings->value('runa.auth', 'invitation_reminder_count');
                $interval = $this->settings->value('runa.auth', 'invitation_reminder_minutes');
                $this->transactions->lockAuthority($connection);
                $candidate = $connection->table('runa_auth.entry_tokens')->where('id', $id)->first();
                $user = $candidate === null ? null : User::where('id', $candidate->user_id)->lockForUpdate()->first();
                $token = $connection->table('runa_auth.entry_tokens')->where('id', $id)->lockForUpdate()->first();
                if ($user !== null && $token !== null && $token->consumed_at === null && $token->invalidated_at === null
                    && $token->remind_at !== null && CarbonImmutable::parse($token->remind_at)->lte(CarbonImmutable::now('UTC'))
                    && CarbonImmutable::parse($token->expires_at)->isFuture() && $token->reminders < $limit && $interval > 0
                    && $token->security_version === $user->security_version && $token->recipient === $user->email) {
                    $this->issueLocked($connection, $user, 'invitation', $token->reminders + 1);
                }
            });
        }
    }
}
