<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\CredentialPolicy;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Modules\Auth\Support\SecurityContext;

final readonly class TrustedDevices
{
    public function __construct(private SecurityContext $context, private CredentialPolicy $policy,
        private CredentialFactors $factors, private EntryTransaction $transactions) {}

    public function apply(Connection $connection, User $user, string $sessionId, Request $request): void
    {
        if (! $this->policy->value('trusted_devices_enabled')) {
            return;
        }
        $token = $request->cookie('runa_trust');
        if (! is_string($token) || ! preg_match('/\A[0-9a-f]{64}\z/D', $token)) {
            return;
        }
        $row = $connection->table('runa_auth.trusted_devices')->where('user_id', $user->id)->where('token_hash', hash('sha256', $token))
            ->where('security_version', $user->security_version)->whereNull('revoked_at')->where('expires_at', '>', now())->first();
        if ($row === null || ! in_array($row->factor_method === 'recovery' ? 'totp' : $row->factor_method, $this->policy->enrolled($user), true) || $row->created_at === null || ! $this->policy->accepts($row->factor_method ?? '')
            || CarbonImmutable::parse($row->created_at)->addDays($this->policy->value('trusted_device_days'))->lte(CarbonImmutable::now('UTC'))) {
            return;
        }
        $connection->table('runa_auth.sessions')->where('public_id', $sessionId)->update(['factor_method' => $row->factor_method, 'factor_verified_at' => now()]);
    }

    public function issue(Request $request): array
    {
        $result = $this->context->run($request, function (Connection $connection, User $user, object $session) use ($request): ?array {
            $method = $request->string('method')->toString();
            if (! $this->policy->value('trusted_devices_enabled')
                || ! $this->factors->verify($connection, $user, $session, $method, $request->string('code')->toString())) {
                return null;
            }
            $secret = bin2hex(random_bytes(32));
            $days = $this->policy->value('trusted_device_days');
            // Bound accumulation per account; oldest trust is revoked before issuance.
            $active = $connection->table('runa_auth.trusted_devices')->where('user_id', $user->id)->whereNull('revoked_at')->orderByDesc('id')->pluck('id');
            $connection->table('runa_auth.trusted_devices')->whereIn('id', $active->slice(19))->update(['revoked_at' => now()]);
            $connection->table('runa_auth.trusted_devices')->insert(['public_id' => (string) Str::uuid7(), 'user_id' => $user->id,
                'token_hash' => hash('sha256', $secret), 'security_version' => $user->security_version,
                'factor_method' => $method, 'created_at' => now(), 'expires_at' => now()->addDays($days)]);
            $this->transactions->record('trusted_device.issued', $user);

            return ['secret' => $secret, 'minutes' => $days * 1440];
        });
        // Encrypted by native cookie middleware, never returned in JSON. Host-only/HttpOnly/SameSite.
        Cookie::queue(Cookie::make('runa_trust', $result['secret'], $result['minutes'], '/', null, config('session.secure'), true, false, 'lax'));

        return ['created' => true];
    }

    public function listFor(string $publicId, ?string $before = null): array
    {
        new TrustedPrincipal($publicId);
        if ($before !== null) {
            new TrustedPrincipal($before);
        }
        $user = User::withTrashed()->where('public_id', $publicId)->first();
        if ($user === null) {
            return [];
        }

        return app('db')->connection()->table('runa_auth.trusted_devices')->where('user_id', $user->id)->whereNull('revoked_at')
            ->where('expires_at', '>', now())->where('security_version', $user->security_version)
            ->when($before !== null, fn ($query) => $query->where('public_id', '<', $before))
            ->orderByDesc('public_id')->limit(100)->get(['public_id', 'created_at', 'expires_at'])->map(fn ($row) => (array) $row)->all();
    }

    public function revoke(string $publicId, string $deviceId): bool
    {
        new TrustedPrincipal($publicId);
        new TrustedPrincipal($deviceId);

        return $this->transactions->run(function (Connection $connection) use ($publicId, $deviceId): bool {
            $this->transactions->lockAuthority($connection);
            $user = User::withTrashed()->where('public_id', $publicId)->lockForUpdate()->first();
            if ($user === null) {
                return false;
            }
            $changed = $connection->table('runa_auth.trusted_devices')->where('user_id', $user->id)->where('public_id', $deviceId)
                ->whereNull('revoked_at')->update(['revoked_at' => now()]) > 0;
            if ($changed) {
                $this->transactions->record('trusted_device.revoked', $user);
            }

            return $changed;
        });
    }
}
