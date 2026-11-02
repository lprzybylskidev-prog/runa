<?php

namespace Runa\Modules\Auth\Actions;

use Illuminate\Support\Collection;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\MutationAccess;
use Runa\Contracts\Modules\MutationDefinition;
use Runa\Contracts\Modules\MutationProof;
use Runa\Contracts\Modules\TransactionalMutation;
use Runa\Modules\Auth\Contracts\BulkUserChange;
use Runa\Modules\Auth\Contracts\SessionSelection;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\BulkUserOperations;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Modules\Auth\Support\LifecyclePlan;
use Runa\Support\Modules\Api\OperationRejected;

final class BulkUserMutation implements TransactionalMutation
{
    /** @var Collection<int,User>|null */
    private ?Collection $users = null;

    /** @var list<User|LifecyclePlan>|null */
    private ?array $plans = null;

    private bool $applied = false;

    private bool $locked = false;

    public function __construct(private readonly BulkUsers $owner, private readonly SessionSelection $selection, private readonly BulkUserChange $change, private readonly string $fingerprint) {}

    public function definition(): MutationDefinition
    {
        $access = BulkUserOperations::access($this->change->operation);
        $grants = [new MutationAccess('runa.auth', new ApiOperation('users.bulk_execute', 'runa.auth.users.read')), new MutationAccess('runa.auth', new ApiOperation($access['operation'], 'runa.auth.'.$access['permission'])), new MutationAccess('runa.permissions', new ApiOperation('selection.users', 'runa.permissions.users.select'))];
        if (BulkUserOperations::delivers($this->change->operation)) {
            $grants[] = new MutationAccess('runa.auth', new ApiOperation('entry.deliver'));
        }
        if ($this->change->operation === 'restore') {
            $grants[] = new MutationAccess('runa.auth', new ApiOperation('administration.users', 'runa.auth.users.deleted.read'));
        }
        $ids = $this->selection->publicIds;
        sort($ids);

        return new MutationDefinition('runa.auth', 'users.bulk.'.$this->change->operation, $grants, $ids, ['runa.auth.users.bulk'], [new MutationProof('runa.auth.users.bulk.'.$this->change->operation, 'both')]);
    }

    public function preflight(): void
    {
        $this->owner->admit($this->selection, $this->change);
    }

    public function lock(): void
    {
        if ($this->locked) {
            throw new \LogicException('Bulk mutation cannot be reused.');
        }
        $this->locked = true;
        $this->users = $this->owner->lock($this->selection);
    }

    public function validate(): void
    {
        $users = $this->users ?? throw new \LogicException('Selection is not locked.');
        $this->owner->admit($this->selection, $this->change);
        $snapshot = $this->owner->snapshot(app('db')->connection(), $users, $this->change);
        if (! hash_equals($snapshot['fingerprint'], $this->fingerprint)) {
            throw new OperationRejected('user_preview_stale', 'runa.auth', 'users.bulk_execute', 409);
        }
        $this->plans = $this->owner->prepare(app('db')->connection(), $users, $this->change);
    }

    public function apply(): void
    {
        if ($this->plans === null || $this->applied) {
            throw new \LogicException('Bulk mutation requires one validated apply.');
        }
        $this->owner->apply(app('db')->connection(), $this->plans, $this->change);
        $this->applied = true;
    }

    public function result(): array
    {
        if (! $this->applied || $this->users === null) {
            throw new \LogicException('Bulk mutation has no result.');
        }

        return ['accounts' => $this->users->count()];
    }

    public function refused(string $reason): void
    {
        $this->users = null;
        $this->plans = null;
        $this->applied = false;
        app(EntryTransaction::class)->securityRefused();
    }
}
