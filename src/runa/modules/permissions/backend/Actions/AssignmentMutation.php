<?php

namespace Runa\Modules\Permissions\Actions;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\MutationAccess;
use Runa\Contracts\Modules\MutationDefinition;
use Runa\Contracts\Modules\MutationProof;
use Runa\Contracts\Modules\TransactionalMutation;
use Runa\Modules\Permissions\Contracts\AssignmentPreview;
use Runa\Modules\Permissions\Support\AccessState;
use Runa\Modules\Permissions\Support\AccessTransaction;

final class AssignmentMutation implements TransactionalMutation
{
    private ?AssignmentPreview $validated = null;

    private bool $locked = false;

    private bool $applied = false;

    public function __construct(private readonly ChangeAssignments $assignments, private readonly AccessState $state,
        private readonly AccessTransaction $transactions, private readonly AssignmentPreview $reviewed) {}

    public function definition(): MutationDefinition
    {
        $subjects = array_values(array_unique(array_filter([$this->reviewed->userPublicId, $this->reviewed->sourceUser])));
        sort($subjects);

        return new MutationDefinition('runa.permissions', 'assignments.change.u'.str_replace('-', '', $this->reviewed->userPublicId), [
            new MutationAccess('runa.permissions', new ApiOperation('access.apply_assignments', 'runa.permissions.access.manage')),
            new MutationAccess('runa.auth', new ApiOperation('eligibility.read', 'runa.auth.eligibility.read')),
        ], $subjects, ['runa.permissions.assignments:'.$this->reviewed->userPublicId], [new MutationProof('runa.permissions.access.manage', 'password')]);
    }

    public function preflight(): void
    {
        $this->assignments->validate($this->reviewed);
    }

    public function lock(): void
    {
        if ($this->locked) {
            throw new \LogicException('Assignment mutation cannot be reused.');
        }
        // Shared registry/authority and sorted Auth subject locks fence all native
        // definition/assignment writers; no foreign persistence is accessed here.
        $this->locked = true;
    }

    public function validate(): void
    {
        if (! $this->locked) {
            throw new \LogicException('Assignments are not locked.');
        }
        $this->validated = $this->assignments->validate($this->reviewed);
    }

    public function apply(): void
    {
        if ($this->validated === null || $this->applied) {
            throw new \LogicException('Assignments require one validated apply.');
        }
        $this->assignments->applyValidated($this->validated);
        $this->applied = true;
    }

    public function result(): array
    {
        if (! $this->applied) {
            throw new \LogicException('Assignments have not applied.');
        }

        return $this->state->user($this->reviewed->userPublicId);
    }

    public function refused(string $reason): void
    {
        $this->validated = null;
        $this->applied = false;
        $this->transactions->refused($reason);
    }
}
