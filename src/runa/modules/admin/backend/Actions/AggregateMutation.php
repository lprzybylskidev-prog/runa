<?php

namespace Runa\Modules\Admin\Actions;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\MutationAccess;
use Runa\Contracts\Modules\MutationDefinition;
use Runa\Contracts\Modules\MutationProof;
use Runa\Contracts\Modules\TransactionalMutation;
use Runa\Modules\Admin\Queries\Projection;
use Runa\Support\Modules\Api\OperationRejected;

/** Source-owned root guard; its result is projected before the native root commits. */
final readonly class AggregateMutation implements TransactionalMutation
{
    /** @param array<string,mixed> $input
     * @param  list<AggregatePart>  $parts
     * @param  list<TransactionalMutation>  $mutations
     */
    public function __construct(private Aggregate $aggregate, private Projection $projection,
        private string $owner, private string $page, private array $input, private string $fingerprint,
        private array $parts, private array $mutations) {}

    public function definition(): MutationDefinition
    {
        return new MutationDefinition('runa.admin', 'aggregate.save', [
            new MutationAccess('runa.admin', new ApiOperation('save', 'runa.admin.enter')),
        ], [], ['runa.admin.aggregate:'.hash('sha256', $this->owner.'.'.$this->page)], [
            new MutationProof('runa.admin.enter', 'password', everyAction: false),
        ]);
    }

    public function preflight(): void
    {
        $this->validate();
    }

    public function lock(): void {}

    public function validate(): void
    {
        $current = $this->aggregate->plan($this->owner, $this->page, $this->input);
        if (! hash_equals($this->fingerprint, $current['fingerprint'])) {
            throw new OperationRejected('admin_revision_stale', 'runa.admin', 'save', 409);
        }
    }

    public function apply(): void {}

    public function result(): array
    {
        $this->aggregate->assertResultAuthority();
        $participants = [];
        foreach ($this->parts as $index => $part) {
            $outcome = $part->participant->project($this->mutations[$index]->result());
            if ($outcome->status !== 'committed' || $outcome->operationId !== null || $outcome->record === null || $outcome->record->publicId !== $part->input->publicId) {
                throw new OperationRejected('admin_participant_incompatible', 'runa.admin', 'save', 409);
            }
            $projected = $this->projection->commandOutcome($this->owner, $this->page, $part->id, $outcome);
            $participants[] = ['id' => $part->id, 'record' => $projected['record']];
        }

        return ['status' => 'committed', 'revision' => $this->projection->page($this->owner, $this->page)['revision'], 'participants' => $participants];
    }

    public function refused(string $reason): void {}
}
