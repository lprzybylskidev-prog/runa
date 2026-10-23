<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Modules\Auth\Support\EntryTransaction;

/** At most one SMTP attempt per token. Ambiguous delivery requires an explicit resend. */
final readonly class DeliverEntryMail
{
    public function __construct(private EntryTransaction $transactions, private LookupIdentity $identities) {}

    public function deliver(string $publicId): void
    {
        new TrustedPrincipal($publicId);
        // Persist the attempt before SMTP; a worker crash cannot replay an actionable message.
        $secret = $this->transactions->run(function (Connection $connection) use ($publicId): ?string {
            $this->transactions->lockAuthority($connection);
            [$user, $token] = $this->locked($connection, $publicId);
            if (! $this->valid($user, $token) || $token->claimed_at !== null || $token->delivery_secret === null) {
                if ($token !== null) {
                    $connection->table('runa_auth.entry_tokens')->where('id', $token->id)->update(['delivery_secret' => null]);
                }

                return null;
            }
            $secret = Crypt::decryptString($token->delivery_secret);
            $connection->table('runa_auth.entry_tokens')->where('id', $token->id)->update(['claimed_at' => now(), 'delivery_secret' => null]);

            return $secret;
        });
        if ($secret === null) {
            return;
        }
        $this->transactions->run(function (Connection $connection) use ($publicId, $secret): void {
            $this->transactions->lockAuthority($connection);
            [$user, $token] = $this->locked($connection, $publicId);
            if (! $this->valid($user, $token) || $token->sent_at !== null) {
                return;
            }
            // Fixed configured origin; fragments are not sent to servers/proxies/access logs.
            $origin = rtrim(config('app.url'), '/');
            $parts = parse_url($origin);
            if (! is_array($parts) || ! isset($parts['host'], $parts['scheme'])
                || ! in_array($parts['scheme'], ['http', 'https'], true)
                || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
                || (app()->environment('production') && $parts['scheme'] !== 'https')) {
                throw new \RuntimeException('Invalid Auth mail origin.');
            }
            $path = $token->purpose === 'invitation' ? '/auth/invitation' : '/auth/verify-email';
            $url = $origin.$path.'#token='.$secret;
            $subject = $token->purpose === 'invitation' ? 'Zaproszenie do aplikacji' : 'Potwierdź adres e-mail';
            // User locks remain held through SMTP handoff to serialize lifecycle/invalidation.
            // No token retry is permitted even if SMTP succeeds and the final DB commit fails.
            try {
                $sent = Mail::raw("Otwórz odnośnik, aby kontynuować:\n".$url."\nJeśli nie oczekujesz tej wiadomości, zignoruj ją.",
                    static fn ($message) => $message->to($token->recipient)->subject($subject));
                if ($sent === null) {
                    throw new \RuntimeException('Auth mail cancelled.');
                }
            } catch (\Throwable) {
                // SMTP exceptions may contain a complete message or provider credentials.
                throw new \RuntimeException('Auth mail delivery requires explicit resend.');
            }
            $connection->table('runa_auth.entry_tokens')->where('id', $token->id)->update(['sent_at' => now()]);
        });
    }

    private function locked(Connection $connection, string $publicId): array
    {
        $candidate = $connection->table('runa_auth.entry_tokens')->where('public_id', $publicId)->first();
        $user = $candidate === null ? null : User::where('id', $candidate->user_id)->lockForUpdate()->first();
        $token = $candidate === null ? null : $connection->table('runa_auth.entry_tokens')->where('id', $candidate->id)->lockForUpdate()->first();

        return [$user, $token];
    }

    private function valid(?User $user, ?object $token): bool
    {
        return $user !== null && $token !== null && $token->consumed_at === null && $token->invalidated_at === null
            && CarbonImmutable::parse($token->expires_at)->isFuture()
            && $token->recipient === $user->email && $token->security_version === $user->security_version
            && ($token->purpose !== 'invitation' || $user->password === null)
            && ($token->purpose !== 'verification' || $user->email_verified_at === null)
            && $this->identities->evaluate($user, CarbonImmutable::now('UTC'))->mayAuthenticate();
    }
}
