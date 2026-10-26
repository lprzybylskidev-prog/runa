<?php

namespace Runa\Modules\Auth\Actions;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use PragmaRX\Google2FA\Google2FA;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\AccountHistoryState;
use Runa\Modules\Auth\Support\CredentialPolicy;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Modules\Auth\Support\SecurityContext;

/** All factor writes/one-time consumption occur under the owning user lock. */
final readonly class CredentialFactors
{
    public function __construct(private SecurityContext $context, private CredentialPolicy $policy,
        private EntryTransaction $transactions, private EntryTokens $tokens, private TwoFactorAuthenticationProvider $totp, private Google2FA $engine) {}

    public function verify(Connection $connection, User $user, object $session, string $method, #[\SensitiveParameter] string $code): bool
    {
        if (! $this->policy->accepts($method)) {
            return false;
        }
        if ($method === 'totp' && $user->totp_secret !== null && preg_match('/\A[0-9]{6}\z/D', $code)) {
            $counter = $this->counter(Crypt::decryptString($user->totp_secret), $code, $user->totp_counter);
            if ($counter === false) {
                return false;
            }
            $user->totp_counter = $counter;
            $user->save();

            return true;
        }
        if ($method === 'recovery' && $this->policy->value('recovery_codes_enabled') && $user->totp_secret !== null) {
            $hash = hash('sha256', $code);
            $hashes = $user->recovery_hashes;
            foreach ($hashes as $index => $stored) {
                if (hash_equals($stored, $hash)) {
                    $before = AccountHistoryState::factors($user);
                    unset($hashes[$index]);
                    $user->recovery_hashes = array_values($hashes);
                    $user->save();
                    $this->transactions->record('two_factor.recovery_used', $user, AccountHistoryState::pairs($before, AccountHistoryState::factors($user)));

                    return true;
                }
            }
        }
        if ($method === 'email' && $user->email_factor && $user->email_verified_at !== null && preg_match('/\A[0-9]{6}\z/D', $code)) {
            $token = $connection->table('runa_auth.entry_tokens')->where('user_id', $user->id)->where('purpose', 'email_otp')
                ->where('session_hash', $session->session_hash)->whereNull('consumed_at')->whereNull('invalidated_at')->lockForUpdate()->first();
            if ($token !== null && $token->security_version === $user->security_version && $token->recipient === $user->email
                && CarbonImmutable::parse($token->expires_at)->isFuture()
                && hash_equals($token->token_hash, hash_hmac('sha256', $token->public_id.':'.$code, config('app.key')))) {
                $connection->table('runa_auth.entry_tokens')->where('id', $token->id)->update(['consumed_at' => now(), 'delivery_secret' => null]);

                return true;
            }
        }

        return false;
    }

    private function counter(#[\SensitiveParameter] string $secret, #[\SensitiveParameter] string $code, ?int $previous): int|false
    {
        // Fortify's installed engine, with replay state persisted atomically instead of in local cache.
        $counter = $this->engine->verifyKeyNewer($secret, $code, $previous ?? 0, 1, intdiv(CarbonImmutable::now('UTC')->getTimestamp(), 30));

        return is_int($counter) ? $counter : false;
    }

    public function handle(Request $request, string $operation): array
    {
        return $this->context->run($request, function (Connection $connection, User $user, object $session) use ($request, $operation): ?array {
            $method = $request->string('method')->toString();
            $code = $request->string('code')->toString();
            if ($operation === 'challenge') {
                if (! $this->verify($connection, $user, $session, $method, $code)) {
                    return null;
                }
                $connection->table('runa_auth.sessions')->where('id', $session->id)->update(['factor_method' => $method, 'factor_verified_at' => now()]);
                $this->transactions->record('two_factor.confirmed', $user);

                return ['confirmed' => true];
            }
            if ($operation === 'email_send') {
                if (! $this->policy->permitted('email') || ! $user->email_factor || $user->email_verified_at === null) {
                    return null;
                }
                $this->tokens->issueLocked($connection, $user, 'email_otp', sessionHash: $session->session_hash);

                return ['accepted' => true];
            }
            if (! Hash::check($request->string('current_password')->toString(), $user->password ?? '')) {
                return null;
            }
            // An enrolled factor must confirm changes, even if ordinary login trusted this device.
            $hasFactors = array_filter($this->policy->enrolled($user), fn ($method) => $this->policy->accepts($method)) !== [];
            if ($hasFactors && ! $this->verify($connection, $user, $session, $method, $code)) {
                return null;
            }
            $before = AccountHistoryState::factors($user);
            if ($operation === 'totp_begin') {
                if (! $this->policy->permitted('totp') || $user->totp_secret !== null) {
                    return null;
                }
                $secret = $this->totp->generateSecretKey(32);
                $user->totp_pending = Crypt::encryptString($secret);
                $user->totp_pending_until = CarbonImmutable::now('UTC')->addMinutes(10);
                $user->save();
                $this->transactions->record('two_factor.enrollment_started', $user, AccountHistoryState::pairs($before, AccountHistoryState::factors($user)));

                $uri = $this->totp->qrCodeUrl(config('app.name'), $user->email, $secret);
                $svg = (new Writer(new ImageRenderer(new RendererStyle(256, 4), new SvgImageBackEnd)))->writeString($uri);

                return ['secret' => $secret, 'uri' => $uri, 'qr_code' => 'data:image/svg+xml;base64,'.base64_encode($svg)];
            }
            $recovery = [];
            if ($operation === 'totp_confirm') {
                if (! $this->policy->permitted('totp') || $user->totp_secret !== null || $user->totp_pending === null
                    || $user->totp_pending_until === null || $user->totp_pending_until->isPast()) {
                    return null;
                }
                $counter = $this->counter(Crypt::decryptString($user->totp_pending), $request->string('enrollment_code')->toString(), null);
                if ($counter === false) {
                    return null;
                }
                $user->totp_secret = $user->totp_pending;
                $user->totp_pending = null;
                $user->totp_pending_until = null;
                $user->totp_counter = $counter;
                $recovery = $this->newRecovery($user);
            } elseif ($operation === 'email_enable') {
                if (! $this->policy->permitted('email') || $user->email_verified_at === null) {
                    return null;
                }
                $user->email_factor = true;
            } elseif ($operation === 'disable') {
                // Required methods cannot be removed through ordinary self service.
                if ($this->policy->requiredMethod() !== 'optional') {
                    return null;
                }
                if ($request->string('target')->toString() === 'totp') {
                    $user->totp_secret = null;
                    $user->totp_pending = null;
                    $user->totp_pending_until = null;
                    $user->totp_counter = null;
                    $user->recovery_hashes = [];
                } else {
                    $user->email_factor = false;
                }
            } elseif ($operation === 'recovery_rotate') {
                if ($user->totp_secret === null || ! $this->policy->value('recovery_codes_enabled')) {
                    return null;
                }
                $recovery = $this->newRecovery($user);
            } else {
                throw new \LogicException('Unknown factor operation.');
            }
            $user->save();
            // Retain current tracked session, revoke all other sessions/trust and invalidate prior proofs.
            $connection->table('runa_auth.sessions')->where('user_id', $user->id)->where('id', '<>', $session->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $connection->table('runa_auth.trusted_devices')->where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $connection->table('runa_auth.step_up_proofs')->where('user_id', $user->id)->delete();
            $connection->table('runa_auth.sessions')->where('id', $session->id)->update([
                'factor_method' => $operation === 'totp_confirm' ? 'totp' : null,
                'factor_verified_at' => $operation === 'totp_confirm' ? now() : null]);
            $changes = AccountHistoryState::pairs($before, AccountHistoryState::factors($user));
            if ($operation === 'recovery_rotate') {
                $changes['spare_codes_replaced'] = ['old' => false, 'new' => true];
            }
            $this->transactions->record('two_factor.'.$operation, $user, $changes);

            return ['updated' => true, 'recovery_codes' => $recovery];
        });
    }

    private function newRecovery(User $user): array
    {
        $codes = $this->policy->value('recovery_codes_enabled') ? array_map(static fn () => bin2hex(random_bytes(16)), range(1, 8)) : [];
        $user->recovery_hashes = array_map(static fn ($code) => hash('sha256', $code), $codes);

        return $codes;
    }
}
