<?php

namespace App\Modules\ModuleProbe\Definitions;

use Runa\Contracts\Modules\WorkDecision;
use Runa\Contracts\Modules\WorkPolicy;
use Runa\Contracts\Modules\WorkStatus;

final readonly class ProbeJobPolicy implements WorkPolicy
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
            return new WorkDecision('cancel', 'probe_cancelled');
        }

        return $available ? new WorkDecision('run', 'probe_ready') : new WorkDecision('retry', 'probe_waiting');
    }

    public function checkpoint(WorkStatus $work): WorkDecision
    {
        return $work->cancellationRequested ? new WorkDecision('cancel', 'probe_safe_stop') : new WorkDecision('run', 'probe_continue');
    }
}
