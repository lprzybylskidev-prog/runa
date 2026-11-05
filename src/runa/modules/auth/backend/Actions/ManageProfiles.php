<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Connection;
use Runa\Contracts\Modules\AuthorityGuard;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Contracts\AuthProfiles;
use Runa\Modules\Auth\Contracts\Identity;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Modules\Auth\Contracts\ProfileChange;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Data\ModuleTransactions;

/** Administrative operations are exposed only through independently permissioned guarded APIs. */
final readonly class ManageProfiles implements AuthProfiles
{
    public function __construct(private EntryTransaction $transactions, private Profiles $profiles, private EmailAddresses $addresses,
        private AuthorityGuard $authority, private StepUp $stepUp, private EntryTokens $tokens, private LookupIdentity $identities, private ModuleTransactions $mutations) {}

    public function edit(string $publicId, int $expectedVersion, ProfileChange $change): Identity
    {
        $mutation = new ProfileMutation($this->profiles, $publicId, $expectedVersion, $change);
        $this->mutations->run([$mutation]);

        return $mutation->identity();
    }

    public function changeEmail(string $publicId, int $expectedVersion, string $email): bool
    {
        return $this->target($publicId, $expectedVersion, function (Connection $connection, User $user) use ($email): bool {
            $this->addresses->startLocked($connection, $user, $email, true);

            return true;
        }, 'runa.auth.email.admin_change');
    }

    public function replaceVerifiedEmail(string $publicId, int $expectedVersion, string $email): bool
    {
        return $this->target($publicId, $expectedVersion, function (Connection $connection, User $user) use ($email): bool {
            if (! $this->profiles->value('admin_email_verification_bypass')) {
                throw new OperationRejected('email_bypass_disabled', 'runa.auth', 'email.admin_bypass', 403);
            }
            $this->addresses->replaceLocked($connection, $user, $email, 'revoke_all', null, true);

            return true;
        }, 'runa.auth.email.admin_bypass');
    }

    public function verifyEmail(string $publicId, int $expectedVersion): bool
    {
        return $this->target($publicId, $expectedVersion, function (Connection $connection, User $user): bool {
            $this->assertAdministrativeVerification();
            if ($this->profiles->value('email_verification') === 'disabled') {
                throw new OperationRejected('email_bypass_disabled', 'runa.auth', 'email.admin_verify', 403);
            }
            $this->addresses->verifyPrimaryLocked($connection, $user);

            return true;
        }, 'runa.auth.email.admin_bypass');
    }

    public function verifySecondary(string $publicId, int $expectedVersion, string $addressId): bool
    {
        new TrustedPrincipal($addressId);

        return $this->target($publicId, $expectedVersion, function (Connection $connection, User $user) use ($addressId): bool {
            $this->assertAdministrativeVerification();
            $this->addresses->verifySecondaryLocked($connection, $user, $addressId);

            return true;
        }, 'runa.auth.email.admin_bypass');
    }

    private function assertAdministrativeVerification(): void
    {
        if (! $this->profiles->value('admin_email_verification_bypass')) {
            throw new OperationRejected('email_bypass_disabled', 'runa.auth', 'email.admin_verify', 403);
        }
    }

    public function requireVerification(string $publicId, int $expectedVersion): bool
    {
        return $this->target($publicId, $expectedVersion, function (Connection $connection, User $user): bool {
            return $this->requireVerificationLocked($connection, $user);
        }, 'runa.auth.email.require_verification');
    }

    /** Private owning apply; native ingress and reviewed batches retain their own proof. */
    public function requireVerificationLocked(Connection $connection, User $user): bool
    {
        $wasVerified = $user->email_verified_at !== null;
        $wasRequired = $user->verification_required;
        $user->email_verified_at = null;
        $user->verification_required = true;
        $user->verification_forced = true;
        $user->version++;
        $user->save();
        $this->tokens->issueLocked($connection, $user, 'verification');
        $this->transactions->record('email.reverification_required', $user, ['email' => ['old' => $user->email, 'new' => $user->email],
            'verified' => ['old' => $wasVerified, 'new' => false], 'verification_required' => ['old' => $wasRequired, 'new' => true]]);

        return true;
    }

    public function addSecondary(string $publicId, int $expectedVersion, string $email): string
    {
        return $this->target($publicId, $expectedVersion, fn (Connection $connection, User $user): string => $this->addresses->secondaryLocked($connection, $user, 'add', $email, null) ?? throw new \LogicException('Secondary address identity unavailable.'));
    }

    public function changeSecondary(string $publicId, int $expectedVersion, string $addressId, string $email): string
    {
        new TrustedPrincipal($addressId);

        return $this->target($publicId, $expectedVersion, fn (Connection $connection, User $user): string => $this->addresses->secondaryLocked($connection, $user, 'change', $email, $addressId) ?? throw new \LogicException('Secondary address identity unavailable.'));
    }

    public function removeSecondary(string $publicId, int $expectedVersion, string $addressId): bool
    {
        new TrustedPrincipal($addressId);

        return $this->target($publicId, $expectedVersion, function (Connection $connection, User $user) use ($addressId): bool {
            $this->addresses->secondaryLocked($connection, $user, 'remove', null, $addressId);

            return true;
        });
    }

    public function notificationEmails(string $publicId): array
    {
        new TrustedPrincipal($publicId);

        return $this->transactions->run(function (Connection $connection) use ($publicId): array {
            $user = User::where('public_id', $publicId)->first();
            if ($user === null || ! $this->profiles->value('secondary_emails_enabled')
                || ! $this->identities->evaluate($user, CarbonImmutable::now('UTC'))->mayOperate()) {
                return [];
            }

            /** @var list<string> $emails */
            $emails = $connection->table('runa_auth.secondary_emails')->where('user_id', $user->id)->whereNull('deleted_at')->whereNotNull('verified_at')->orderBy('id')->pluck('email')->all();

            return $emails;
        });
    }

    /** @template T
     * @param  Closure(Connection, User): T  $work
     * @return T
     */
    private function target(string $publicId, int $version, Closure $work, ?string $proofAction = null): mixed
    {
        new TrustedPrincipal($publicId);

        return $this->transactions->run(function (Connection $connection) use ($publicId, $version, $work, $proofAction): mixed {
            $this->transactions->lockAuthority($connection);
            // Lock the current actor before the target, consistently with the credential APIs.
            if ($proofAction !== null) {
                $this->stepUp->assertVerified($proofAction, everyAction: true);
            }
            $user = User::where('public_id', $publicId)->lockForUpdate()->first() ?? throw new IdentityConflict('identity_missing');
            $this->profiles->version($user, $version);
            $this->authority->assertMayRemove($connection, $proofAction === 'runa.auth.email.require_verification' ? [$publicId] : []);

            return $work($connection, $user);
        });
    }
}
