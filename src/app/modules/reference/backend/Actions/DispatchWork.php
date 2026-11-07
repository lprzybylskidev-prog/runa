<?php

namespace App\Modules\Reference\Actions;

use Closure;
use Illuminate\Support\Facades\DB;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\MutationAccess;
use Runa\Contracts\Modules\MutationDefinition;
use Runa\Contracts\Modules\MutationProof;
use Runa\Contracts\Modules\TransactionalMutation;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Contracts\AuditRecords;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Work\ModuleWork;

/** Durable intent and explicit Audit commit with one action-specific password proof. */
final class DispatchWork implements TransactionalMutation
{
    private ?Closure $dispatch = null;

    private ?string $workId = null;

    public function definition(): MutationDefinition
    {
        return new MutationDefinition('app.reference', 'work.dispatch', [
            new MutationAccess('app.reference', new ApiOperation('work.dispatch', 'app.reference.work.dispatch')),
        ], [], ['app.reference.work:dispatch'], [new MutationProof('app.reference.work.dispatch', 'password')]);
    }

    public function preflight(): void
    {
        // Existing job admission happens before the root seals its health scope.
        $this->dispatch = app(ModuleWork::class)->prepareDispatch('app.reference', 'reference.work', []);
    }

    public function lock(): void {}

    public function validate(): void {}

    public function apply(): void
    {
        if ($this->workId !== null || $this->dispatch === null || DB::connection()->transactionLevel() !== 1) {
            throw new \LogicException('Work requires one prepared root apply.');
        }
        $this->workId = ($this->dispatch)();
        app(ModuleApis::class)->resolve('runa.audit', AuditRecords::class, '1.0.0')->record(
            new AuditEvent('app.reference', 'work.dispatched', context: ['work_id' => $this->workId]));
    }

    public function result(): array
    {
        return ['work_id' => $this->workId ?? throw new \LogicException('Work was not admitted.'), 'status' => 'admitted'];
    }

    public function refused(string $reason): void
    {
        $this->dispatch = null;
        $this->workId = null;
    }
}
