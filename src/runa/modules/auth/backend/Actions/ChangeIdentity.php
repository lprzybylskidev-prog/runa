<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\QueryException;
use Runa\Contracts\Modules\AuthorityGuard;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Audit\Contracts\AuditEntity;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Contracts\AuditRecords;
use Runa\Modules\Auth\Contracts\AuthLifecycle;
use Runa\Modules\Auth\Contracts\Identity;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Modules\Auth\Contracts\LifecycleChange;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Modules\Auth\Models\User;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Data\AuthorityLock;
use Runa\Support\Modules\Settings\ModuleSettings;

/** Trusted owning use cases. Administrative ingress additionally requires API permission. */
final readonly class ChangeIdentity implements AuthLifecycle
{
    public function __construct(
        private DatabaseManager $database, private ModuleApis $apis, private AuthorityLock $lock,
        private AuthorityGuard $authority, private ModuleSettings $settings,
    ) {}

    public function create(NewIdentity $input): Identity
    {
        return $this->transaction('identity.create', null, function (Connection $connection) use ($input): Identity {
            // Read settings while holding the registry lock, before domain/advisory locks.
            $this->registryLock($connection);
            $mode = $this->settings->value('runa.auth', 'identifier_mode');
            if ($mode === 'login' && $input->login === null) {
                throw new IdentityConflict('login_required');
            }
            $this->lock->acquire($connection);
            $this->guard($connection, []);
            $user = new User;
            $user->forceFill([
                'name' => $input->name, 'first_name' => $input->firstName, 'surname' => $input->surname,
                'email' => $input->email, 'email_claim' => $input->email,
                'login' => $input->login, 'login_claim' => $input->login,
                'active' => $input->active, 'approved' => $input->approved,
                'version' => 1, 'security_version' => 1,
                'verification_required' => $this->settings->value('runa.auth', 'email_verification') !== 'disabled',
            ]);
            app(EmailAddresses::class)->available($connection, $user, $input->email, true);
            $user->save();
            // No names, addresses, login, password or token in lifecycle history.
            $this->record('identity.created', $user, ['active' => $input->active, 'approved' => $input->approved]);

            return $user->identity();
        });
    }

    public function change(string $publicId, int $expectedVersion, LifecycleChange $change): Identity
    {
        new TrustedPrincipal($publicId);
        if ($expectedVersion < 1) {
            throw new \InvalidArgumentException('Invalid identity version.');
        }

        return $this->transaction('identity.'.$change->operation, $publicId, function (Connection $connection) use ($publicId, $expectedVersion, $change): Identity {
            $this->registryLock($connection);
            $reuse = $this->settings->value('runa.auth', 'reuse_deleted_identifiers');
            $this->lock->acquire($connection);
            $user = User::withTrashed()->where('public_id', $publicId)->lockForUpdate()->first()
                ?? throw new IdentityConflict('identity_missing');
            if ($user->version !== $expectedVersion) {
                throw new IdentityConflict('identity_stale');
            }
            // Only explicit lifecycle removal excludes this identity from the invariant.
            // Activation, restore and profile-independent security locks must remain recoverable.
            $this->guard($connection, in_array($change->operation, ['deactivate', 'delete'], true) ? [$publicId] : []);
            if ($user->trashed() && $change->operation !== 'restore') {
                throw new IdentityConflict('identity_deleted');
            }
            $before = $this->state($user);
            $now = CarbonImmutable::now('UTC');
            $deactivationReached = $user->deactivate_at !== null && $now->gte($user->deactivate_at);
            switch ($change->operation) {
                case 'approve':
                    $user->approved = true;
                    break;
                case 'activate':
                    $user->active = true;
                    $user->activate_at = null;
                    $user->deactivate_at = null;
                    break;
                case 'deactivate':
                    $user->active = false;
                    $user->activate_at = null;
                    $user->deactivate_at = null;
                    break;
                case 'delete':
                    $user->deleted_at = $now;
                    $user->active = false;
                    $user->activate_at = null;
                    $user->deactivate_at = null;
                    if ($reuse === true) {
                        $user->email_claim = null;
                        $user->login_claim = null;
                    }
                    break;
                case 'restore':
                    if (! $user->trashed()) {
                        return $user->identity();
                    }
                    app(EmailAddresses::class)->available($connection, $user, $user->email, true);
                    $user->email_claim = $user->email;
                    $user->login_claim = $user->login;
                    $user->deleted_at = null;
                    $user->active = false;
                    $user->activate_at = null;
                    $user->deactivate_at = null;
                    break;
                case 'schedule_activation':
                case 'schedule_deactivation':
                    if ($change->at !== null && CarbonImmutable::parse($change->at)->lte($now)) {
                        throw new IdentityConflict('schedule_must_be_future');
                    }
                    $user->{$change->operation === 'schedule_activation' ? 'activate_at' : 'deactivate_at'} = $change->at;
                    break;
                case 'set_expiry':
                    $user->expires_at = $change->at;
                    break;
                case 'set_attempt_lock':
                    $user->attempt_locked_until = $change->at;
                    break;
                case 'set_security_lock':
                    $user->security_locked = $change->enabled;
                    break;
                case 'require_password_change':
                    $user->password_change_required = $change->enabled;
                    break;
            }
            if ($user->activate_at !== null && $user->deactivate_at !== null && $user->activate_at->gte($user->deactivate_at)) {
                throw new IdentityConflict('invalid_schedule_order');
            }
            if (! $user->isDirty()) {
                return $user->identity();
            }
            if (in_array($change->operation, ['activate', 'deactivate', 'delete', 'restore'], true)
                || ($change->operation === 'set_security_lock' && $change->enabled)
                || ($change->operation === 'schedule_deactivation' && $deactivationReached)
                || ($change->operation === 'schedule_activation' && ! $user->active)) {
                // Irreversible revocation; later activation/restore never revalidates old sessions/trust.
                $user->security_version++;
                $user->remember_token = null;
                app(SessionStore::class)->revokeUser($connection, $user);
            }
            $user->version++;
            $user->save();
            $after = $this->state($user);
            $changes = [];
            foreach ($after as $key => $value) {
                if ($before[$key] !== $value) {
                    $changes[$key] = ['old' => $before[$key], 'new' => $value];
                }
            }
            $this->record('identity.'.$change->operation, $user, $changes);

            return $user->identity();
        });
    }

    private function registryLock(Connection $connection): void
    {
        $connection->table('shared.module_registry_state')->where('id', 1)->sharedLock()->first()
            ?? throw new \RuntimeException('Module registry unavailable.');
    }

    private function guard(Connection $connection, array $publicIds): void
    {
        try {
            $this->authority->assertMayRemove($connection, $publicIds);
        } catch (\RuntimeException) {
            throw new IdentityConflict('authority_change_refused');
        }
    }

    /** @param Closure(Connection): Identity $write */
    private function transaction(string $action, ?string $publicId, Closure $write): Identity
    {
        $connection = $this->database->connection();
        $root = $connection->transactionLevel() === 0;
        try {
            return $connection->transaction(fn () => $write($connection));
        } catch (QueryException $error) {
            if ($error->getCode() !== '23505') {
                throw $error;
            }
            // Unique DB arbitration also covers a restore racing a new account.
            $conflict = new IdentityConflict('identifier_conflict');
        } catch (IdentityConflict $error) {
            $conflict = $error;
        }
        if ($root) {
            $this->audit()->attempt(new AuditEvent('runa.auth', $action, 'refused',
                entities: $publicId === null ? [] : [new AuditEntity('runa.auth', 'user', $publicId)],
                context: ['reason' => $conflict->getMessage()]));
        }
        // Nested callers must roll their root back before recording an independent refusal.
        throw $conflict;
    }

    private function audit(): AuditRecords
    {
        return $this->apis->resolve('runa.audit', AuditRecords::class, '1.0.0');
    }

    private function record(string $action, User $user, array $changes): void
    {
        $this->audit()->record(new AuditEvent('runa.auth', $action,
            entities: [new AuditEntity('runa.auth', 'user', $user->public_id)], changes: $changes));
    }

    private function state(User $user): array
    {
        $state = [];
        foreach (['active', 'approved', 'deleted_at', 'activate_at', 'deactivate_at', 'expires_at',
            'security_locked', 'attempt_locked_until', 'password_change_required', 'security_version', 'version'] as $key) {
            $value = $user->getAttribute($key);
            $state[$key] = $value instanceof \DateTimeInterface ? $value->format('Y-m-d\TH:i:s.u\Z') : $value;
        }

        return $state;
    }
}
