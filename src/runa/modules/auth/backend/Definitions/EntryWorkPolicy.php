<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\WorkDecision;
use Runa\Contracts\Modules\WorkPolicy;
use Runa\Contracts\Modules\WorkStatus;

final class EntryWorkPolicy implements WorkPolicy
{
    public function schemaVersion(): int
    {
        return 1;
    }

    public function pending(WorkStatus $work, bool $available): WorkDecision
    {
        if (in_array($work->status, ['failed', 'recovery_required'], true) || $work->cancellationRequested) {
            return new WorkDecision('cancel', 'explicit_resend_required');
        }

        return $available ? new WorkDecision('run', 'entry_ready') : new WorkDecision('retry', 'entry_unavailable');
    }

    public function checkpoint(WorkStatus $work): WorkDecision
    {
        return new WorkDecision('run', 'entry_continue');
    }
}
