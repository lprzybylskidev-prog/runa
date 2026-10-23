<?php

namespace App\Modules\Reference\Definitions;

use Runa\Contracts\Modules\WorkDecision;
use Runa\Contracts\Modules\WorkPolicy;
use Runa\Contracts\Modules\WorkStatus;

final readonly class ReferenceJobPolicy implements WorkPolicy
{
    public function schemaVersion(): int
    {
        return 1;
    }

    public function pending(WorkStatus $work, bool $available): WorkDecision
    {
        if (in_array($work->status, ['failed', 'recovery_required'], true)) {
            return new WorkDecision('cancel', 'stateless_reconciled');
        }
        if ($work->cancellationRequested) {
            return new WorkDecision('cancel', 'reference_cancelled');
        }

        return $available ? new WorkDecision('run', 'reference_ready') : new WorkDecision('retry', 'reference_waiting');
    }

    public function checkpoint(WorkStatus $work): WorkDecision
    {
        return $work->cancellationRequested ? new WorkDecision('cancel', 'reference_safe_stop') : new WorkDecision('run', 'reference_continue');
    }
}
