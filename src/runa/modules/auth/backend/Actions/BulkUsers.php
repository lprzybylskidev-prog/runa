<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\AuthorityGuard;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\Modules\Auth\Contracts\AuthBulkUsers;
use Runa\Modules\Auth\Contracts\BulkUserChange;
use Runa\Modules\Auth\Contracts\BulkUserSelection;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Modules\Auth\Contracts\LifecycleChange;
use Runa\Modules\Auth\Contracts\OperatorAuthority;
use Runa\Modules\Auth\Contracts\SessionSelection;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Modules\Auth\Support\BulkUserOperations;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Modules\Auth\Support\LifecyclePlan;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Data\ModuleTransactions;

final readonly class BulkUsers implements AuthBulkUsers
{
    public function __construct(private EntryTransaction $transactions, private BulkUserSelection $selector, private OperationGuard $guard, private ModuleTransactions $mutations, private AuthorityGuard $authority) {}

    public function preview(SessionSelection $selection, BulkUserChange $change): array
    {
        $this->admit($selection, $change);

        try {
            return $this->transactions->run(function (Connection $connection) use ($selection, $change): array {
                $this->transactions->lockAuthority($connection);
                $users = $this->lock($selection);
                $plans = $this->prepare($connection, $users, $change);

                return $this->snapshot($connection, $users, $change, $plans);
            });
        } catch (IdentityConflict $error) {
            throw $this->refusal($error, 'users.bulk_preview');
        }
    }

    public function execute(SessionSelection $selection, BulkUserChange $change, string $fingerprint): array
    {
        $this->admit($selection, $change);
        $mutation = new BulkUserMutation($this, $selection, $change, $fingerprint);
        try {
            $this->mutations->run([$mutation]);
        } catch (IdentityConflict $error) {
            throw $this->refusal($error, 'users.bulk_execute');
        } catch (QueryException $error) {
            if ($error->getCode() !== '23505') {
                throw $error;
            }
            throw new OperationRejected('identifier_conflict', 'runa.auth', 'users.bulk_execute', 409);
        }

        return $mutation->result();
    }

    public function admit(SessionSelection $selection, BulkUserChange $change): void
    {
        // Reserve the hundredth transaction subject for the acting administrator.
        if ($selection->all || count($selection->publicIds) > 99 || $selection->publicIds === []) {
            throw new OperationRejected('user_selection_invalid', 'runa.auth', 'users.bulk_preview', 422);
        }
        if (! BulkUserOperations::available($change->operation)) {
            throw new OperationRejected('admin_operation_unavailable', 'runa.auth', 'users.bulk_preview', 404);
        }
        $access = BulkUserOperations::access($change->operation);
        $this->guard->enter('runa.auth', new ApiOperation($access['operation'], 'runa.auth.'.$access['permission']));
        if (in_array($change->operation, ['restore', 'toggle_deletion'], true)) {
            $this->guard->enter('runa.auth', new ApiOperation('administration.users', 'runa.auth.users.deleted.read'));
        }
        $this->selector->authorize($selection);
    }

    /** @return Collection<int,User> */
    public function lock(SessionSelection $selection): Collection
    {
        $users = User::withTrashed()->whereIn('public_id', $selection->publicIds)->orderBy('public_id')->lockForUpdate()->get();
        if ($users->count() !== count($selection->publicIds)) {
            throw new IdentityConflict('identity_missing');
        }

        return $users;
    }

    /** @param Collection<int,User> $users
     * @param  list<User|LifecyclePlan>  $plans  Validated in-memory plans for impact projection; validation needs only the fingerprint.
     * @return array{fingerprint:string,accounts:int,affected_accounts:int,sessions:int,trusted_devices:int,skipped_accounts:int,ineligible_accounts:int}
     */
    public function snapshot(Connection $connection, Collection $users, BulkUserChange $change, array $plans = []): array
    {
        $state = [];
        foreach ($users as $user) {
            $state[] = [$user->public_id, $user->version, $user->security_version, $user->preference_revision];
        }
        $protected = $this->protectedAccounts($connection, $users);
        $targets = $users->reject(fn (User $user): bool => in_array($user->public_id, $protected, true));
        $ineligible = $this->ineligibleAccounts($targets, $change);
        $targets = $targets->reject(fn (User $user): bool => in_array($user->public_id, $ineligible, true));
        $related = [];
        foreach (['sessions', 'trusted_devices', 'entry_tokens', 'policy_exceptions'] as $table) {
            $rows = $connection->table('runa_auth.'.$table)->whereIn('user_id', $targets->modelKeys());
            if ($table === 'entry_tokens') {
                $rows->whereNull('consumed_at')->whereNull('invalidated_at');
            } else {
                $rows->whereNull('revoked_at');
            }
            // Only safe state enters the review fingerprint; never credentials or recipients.
            $columns = match ($table) {
                'policy_exceptions' => ['public_id', 'user_id', 'version', 'expires_at'],'entry_tokens' => ['public_id', 'user_id', 'purpose', 'expires_at'],default => ['public_id', 'user_id']
            };
            $data = $rows->orderBy('public_id')->limit(10001)->get($columns);
            if ($data->count() > 10000) {
                throw new OperationRejected('user_selection_limit', 'runa.auth', 'users.bulk_preview', 422);
            }
            $related[$table] = $data->all();
        }
        $revoked = [];
        foreach ($plans as $plan) {
            if ($plan instanceof LifecyclePlan && $plan->revoke && $plan->user->isDirty()) {
                $revoked[] = $plan->user->id;
            }
        }
        if (in_array($change->operation, ['password_reset', 'factor_reset'], true)) {
            $revoked = $targets->modelKeys();
        }
        $trust = $change->operation === 'revoke_trust' ? $targets->modelKeys() : $revoked;
        $sessions = count(array_filter($related['sessions'], fn ($row) => in_array($row->user_id, $revoked, true)));
        $devices = count(array_filter($related['trusted_devices'], fn ($row) => in_array($row->user_id, $trust, true)));
        $revision = $connection->table('shared.modules')->where('module_id', 'runa.auth')->value('settings_revision');
        $affected = 0;
        foreach ($plans as $plan) {
            if ($plan instanceof LifecyclePlan) {
                $affected += (int) $plan->user->isDirty();

                continue;
            }
            $rows = match ($change->operation) {
                'revoke_trust' => $related['trusted_devices'],
                'revoke_exceptions' => $related['policy_exceptions'],
                'invalidate_invitation' => array_filter($related['entry_tokens'], fn ($row) => $row->purpose === 'invitation'),
                default => null,
            };
            $affected += (int) ($rows !== null ? count(array_filter($rows, fn ($row) => $row->user_id === $plan->id)) > 0 : ($change->operation !== 'remove_avatar' || $plan->avatar_path !== null));
        }

        return ['fingerprint' => hash('sha256', json_encode([$revision, $change, $state, $related, $protected, $ineligible], JSON_THROW_ON_ERROR)), 'accounts' => $users->count(), 'affected_accounts' => $affected, 'sessions' => $sessions, 'trusted_devices' => $devices, 'skipped_accounts' => count($protected) + count($ineligible), 'ineligible_accounts' => count($ineligible)];
    }

    /** @param Collection<int,User> $users
     * @return list<User|LifecyclePlan>
     */
    public function prepare(Connection $connection, Collection $users, BulkUserChange $change): array
    {
        $protected = $this->protectedAccounts($connection, $users);
        $users = $users->reject(fn (User $user): bool => in_array($user->public_id, $protected, true));
        $ineligible = $this->ineligibleAccounts($users, $change);
        $users = $users->reject(fn (User $user): bool => in_array($user->public_id, $ineligible, true));
        // Resolve all binary changes from the same locked snapshot before any plan mutates a model.
        $changes = [];
        $exclude = [];
        foreach ($users as $user) {
            $resolved = match ($change->operation) {
                'toggle_activation' => new LifecycleChange($user->active ? 'deactivate' : 'activate'),
                'toggle_deletion' => new LifecycleChange($user->trashed() ? 'restore' : 'delete'),
                'toggle_security_lock' => new LifecycleChange('set_security_lock', enabled: ! $user->security_locked),
                default => in_array($change->operation, BulkUserChange::LIFECYCLE, true)
                    ? new LifecycleChange($change->operation, $change->at, $change->enabled) : null,
            };
            $changes[$user->id] = $resolved;
            if (in_array($resolved?->operation ?? $change->operation, ['deactivate', 'delete', 'factor_reset', 'require_verification'], true)) {
                $exclude[] = $user->public_id;
            }
        }
        $this->authority->assertMayRemove($connection, $exclude);
        $plans = [];
        foreach ($users as $user) {
            $resolved = $changes[$user->id];
            if ($user->trashed() && $resolved?->operation !== 'restore') {
                throw new IdentityConflict('identity_deleted');
            }
            if ($resolved !== null) {
                $plans[] = app(ChangeIdentity::class)->prepareChangeLocked($connection, $user, $resolved);

                continue;
            }
            if ($change->operation === 'password_reset' || $change->operation === 'factor_reset') {
                app(ManageSecurity::class)->validateChangeLocked($connection, $user, $change->operation === 'factor_reset');
            }
            $plans[] = $user;
        }

        return $plans;
    }

    /** @param Collection<int,User> $users
     * @return list<string>
     */
    private function ineligibleAccounts(Collection $users, BulkUserChange $change): array
    {
        if (! in_array($change->operation, ['invite', 'password_reset'], true)) {
            return [];
        }
        $now = CarbonImmutable::now('UTC');

        return $users->filter(fn (User $user): bool => ($change->operation === 'invite' ? $user->password !== null : $user->password === null)
            || ! app(LookupIdentity::class)->evaluate($user, $now)->mayAuthenticate())->pluck('public_id')->all();
    }

    private function refusal(IdentityConflict $error, string $operation): OperationRejected
    {
        $reason = match ($error->getMessage()) {
            'schedule_must_be_future', 'invalid_schedule_order', 'identity_deleted', 'identifier_conflict', 'invitation_unavailable', 'password_reset_unavailable' => $error->getMessage(),
            default => 'user_change_refused',
        };

        return new OperationRejected($reason, 'runa.auth', $operation, 409);
    }

    /** @param Collection<int,User> $users
     * @return list<string>
     */
    private function protectedAccounts(Connection $connection, Collection $users): array
    {
        if ($users->isEmpty()) {
            return [];
        }

        return app(OperatorAuthority::class)->administrators($connection, $users->pluck('public_id')->all());
    }

    /** @param list<User|LifecyclePlan> $plans */
    public function apply(Connection $connection, array $plans, BulkUserChange $change): void
    {
        foreach ($plans as $plan) {
            if ($plan instanceof LifecyclePlan) {
                app(ChangeIdentity::class)->applyChangeLocked($connection, $plan);

                continue;
            }
            $user = $plan;
            match ($change->operation) {
                'invite' => app(EntryTokens::class)->issueLocked($connection, $user, 'invitation') ?: throw new IdentityConflict('invitation_unavailable'),
                'invalidate_invitation' => app(EntryTokens::class)->invalidateLocked($connection, $user),
                'require_verification' => app(ManageProfiles::class)->requireVerificationLocked($connection, $user),
                'password_reset','factor_reset' => app(ManageSecurity::class)->applyChangeLocked($connection, $user, $change->operation === 'factor_reset'),
                'revoke_trust' => app(TrustedDevices::class)->revokeAllLocked($connection, $user),
                'reset_preferences' => app(AccountPreferences::class)->resetAccountLocked($connection, $user, $user->preference_revision),
                'remove_avatar' => app(Avatars::class)->removeLocked($connection, $user),
                'revoke_exceptions' => app(Policies::class)->revokeExceptionsLocked($connection, $user),
                default => throw new \LogicException('Unsupported bulk operation.'),
            };
        }
    }
}
