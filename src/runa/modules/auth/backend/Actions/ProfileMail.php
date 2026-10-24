<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Mail\AuthMessage;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Support\Modules\Settings\ModuleSettings;
use Runa\Support\Modules\Work\ModuleWork;

/** Private recipient-bound links; queue envelopes contain references only. */
final readonly class ProfileMail
{
    public function __construct(private EntryTransaction $transactions, private LookupIdentity $identities,
        private ModuleSettings $settings, private ModuleWork $work) {}

    public function issueLocked(Connection $connection, User $user, string $purpose, string $recipient, ?int $changeId = null, ?int $secondaryId = null): void
    {
        if ($connection !== app('db')->connection() || $connection->transactionLevel() < 1) {
            throw new \LogicException('Profile mail requires its owning transaction.');
        }
        $id = (string) Str::uuid7();
        $secret = $purpose === 'notice' ? null : bin2hex(random_bytes(32));
        $enqueue = $this->work->prepareDispatch('runa.auth', 'profile.deliver', ['mail_id' => $id]);
        $connection->table('runa_auth.profile_mail')->insert([
            'public_id' => $id, 'user_id' => $user->id, 'purpose' => $purpose, 'recipient' => $recipient,
            'change_id' => $changeId, 'secondary_id' => $secondaryId, 'security_version' => $user->security_version,
            'token_hash' => $secret === null ? null : hash('sha256', $secret), 'delivery_secret' => $secret === null ? null : Crypt::encryptString($secret),
            'expires_at' => now()->addMinutes($this->settings->value('runa.auth', 'email_change_minutes')), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $enqueue();
    }

    public function validLocked(Connection $connection, ?User $user, ?object $mail): bool
    {
        if ($user === null || $mail === null || $mail->consumed_at !== null || ! CarbonImmutable::parse($mail->expires_at)->isFuture()) {
            return false;
        }
        // The old-address notice intentionally survives the address/generation change it reports.
        if ($mail->purpose === 'notice') {
            return true;
        }
        if (! $this->identities->evaluate($user, CarbonImmutable::now('UTC'))->mayAuthenticate()) {
            return false;
        }
        if ($mail->security_version !== $user->security_version) {
            return false;
        }
        if ($mail->purpose === 'secondary') {
            return $this->settings->value('runa.auth', 'secondary_emails_enabled') && $connection->table('runa_auth.secondary_emails')
                ->where('id', $mail->secondary_id)->where('user_id', $user->id)->where('email', $mail->recipient)->whereNull('deleted_at')->whereNull('verified_at')->exists();
        }
        $change = $connection->table('runa_auth.email_changes')->where('id', $mail->change_id)->where('user_id', $user->id)->first();

        return $change !== null && $change->cancelled_at === null && $change->completed_at === null
            && CarbonImmutable::parse($change->expires_at)->isFuture() && $change->old_email === $user->email
            && $change->security_version === $user->security_version
            && $mail->recipient === ($mail->purpose === 'primary_new' ? $change->new_email : $change->old_email);
    }

    public function deliver(string $publicId): void
    {
        new TrustedPrincipal($publicId);
        // Persist the single SMTP attempt separately, just like credential mail. Never retry an ambiguous send.
        $claimed = $this->transactions->run(function (Connection $connection) use ($publicId): ?array {
            $this->transactions->lockAuthority($connection);
            [$user, $mail] = $this->locked($connection, $publicId);
            if (! $this->validLocked($connection, $user, $mail) || $mail->claimed_at !== null) {
                if ($mail !== null) {
                    $connection->table('runa_auth.profile_mail')->where('id', $mail->id)->update(['delivery_secret' => null]);
                }

                return null;
            }
            $secret = $mail->delivery_secret === null ? null : Crypt::decryptString($mail->delivery_secret);
            if ($mail->purpose !== 'notice' && $secret === null) {
                return null;
            }
            $connection->table('runa_auth.profile_mail')->where('id', $mail->id)->update(['claimed_at' => now(), 'delivery_secret' => null]);

            return [$secret];
        });
        if ($claimed === null) {
            return;
        }
        $this->transactions->run(function (Connection $connection) use ($publicId, $claimed): void {
            $this->transactions->lockAuthority($connection);
            [$user, $mail] = $this->locked($connection, $publicId);
            if (! $this->validLocked($connection, $user, $mail) || $mail->sent_at !== null) {
                return;
            }
            $origin = rtrim(config('app.url'), '/');
            $parts = parse_url($origin);
            if (! is_array($parts) || ! isset($parts['host'], $parts['scheme']) || ! in_array($parts['scheme'], ['http', 'https'], true)
                || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
                || (app()->environment('production') && $parts['scheme'] !== 'https')) {
                throw new \RuntimeException('Invalid Auth mail origin.');
            }
            $notice = $mail->purpose === 'notice';
            $body = $notice ? 'Główny adres e-mail Twojego konta został zmieniony. Jeśli nie zlecono tej zmiany, skontaktuj się z administratorem.'
                : 'Potwierdź adres e-mail przypisany do Twojego konta. Jeśli nie oczekujesz tej wiadomości, zignoruj ją.';
            try {
                $sent = Mail::to($mail->recipient)->send(new AuthMessage(
                    $notice ? 'Zmiana adresu e-mail' : 'Potwierdź adres e-mail', $body,
                    $notice ? null : 'Potwierdź adres e-mail',
                    $notice ? null : $origin.'/auth/confirm-address#token='.$claimed[0]));
                if ($sent === null) {
                    throw new \RuntimeException('Auth mail cancelled.');
                }
            } catch (\Throwable) {
                throw new \RuntimeException('Auth mail delivery requires explicit resend.');
            }
            $connection->table('runa_auth.profile_mail')->where('id', $mail->id)->update(['sent_at' => now()]);
        });
    }

    private function locked(Connection $connection, string $publicId): array
    {
        $mail = $connection->table('runa_auth.profile_mail')->where('public_id', $publicId)->first();
        $user = $mail === null ? null : User::withTrashed()->where('id', $mail->user_id)->lockForUpdate()->first();
        $mail = $mail === null ? null : $connection->table('runa_auth.profile_mail')->where('id', $mail->id)->lockForUpdate()->first();

        return [$user, $mail];
    }
}
