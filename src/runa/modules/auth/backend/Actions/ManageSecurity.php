<?php

namespace Runa\Modules\Auth\Actions;

use Illuminate\Database\Connection;
use Runa\Contracts\Modules\AuthorityGuard;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Contracts\AuthSecurity;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\EntryTransaction;

final readonly class ManageSecurity implements AuthSecurity
{
    public function __construct(private EntryTransaction $transactions, private EntryTokens $tokens,
        private SessionStore $sessions, private TrustedDevices $devices, private AuthorityGuard $authority, private StepUp $stepUp) {}

    public function requestPasswordReset(string $publicId, int $expectedVersion): bool
    {
        return $this->change($publicId, $expectedVersion, false);
    }

    public function resetFactors(string $publicId, int $expectedVersion): bool
    {
        return $this->change($publicId, $expectedVersion, true);
    }

    private function change(string $publicId, int $expectedVersion, bool $factors): bool
    {
        new TrustedPrincipal($publicId);

        return $this->transactions->run(function (Connection $connection) use ($publicId, $expectedVersion, $factors): bool {
            $this->transactions->lockAuthority($connection);
            $this->stepUp->assertVerified($factors ? 'runa.auth.two_factor.reset' : 'runa.auth.password.admin_reset', 'both', everyAction: true);
            $user = User::where('public_id', $publicId)->lockForUpdate()->first();
            if ($user === null || $user->version !== $expectedVersion) {
                return false;
            }
            $this->authority->assertMayRemove($connection, [$publicId]);
            $this->sessions->revokeUser($connection, $user);
            $user->security_version++;
            $user->version++;
            $user->remember_token = null;
            if ($factors) {
                $user->totp_secret = null;
                $user->totp_pending = null;
                $user->totp_pending_until = null;
                $user->totp_counter = null;
                $user->recovery_hashes = [];
                $user->email_factor = false;
            }
            $user->save();
            $connection->table('runa_auth.step_up_proofs')->where('user_id', $user->id)->delete();
            $connection->table('runa_auth.entry_tokens')->where('user_id', $user->id)->whereNull('consumed_at')->whereNull('invalidated_at')
                ->update(['invalidated_at' => now(), 'delivery_secret' => null]);
            $issued = $factors ? true : $this->tokens->issueLocked($connection, $user, 'reset');
            $this->transactions->record($factors ? 'two_factor.admin_reset' : 'password.admin_reset', $user);

            return $issued;
        });
    }

    public function trustedDevices(string $publicId, ?string $before = null): array
    {
        return $this->devices->listFor($publicId, $before);
    }

    public function revokeDevice(string $publicId, string $deviceId): bool
    {
        return $this->devices->revoke($publicId, $deviceId);
    }
}
