<?php

namespace Tests\Fixtures;

use Closure;
use Runa\Contracts\Modules\WorkDecision;
use Runa\Contracts\Modules\WorkStatus;
use Runa\Support\Modules\Work\WorkContext;

final class WorkCalls
{
    public static ?Closure $pending = null;

    public static ?Closure $checkpoint = null;

    public static ?Closure $handle = null;

    public static ?Closure $construct = null;

    public static int $constructed = 0;

    public static int $executed = 0;

    public static int $maxAttempts = 3;

    public static int $retrySeconds = 2;

    public static int $leaseSeconds = 30;

    public static int $schema = 1;

    public static function construct(): void
    {
        self::$constructed++;
        if (self::$construct !== null) {
            (self::$construct)();
        }
    }

    public static function pending(WorkStatus $work, bool $available): WorkDecision
    {
        if (self::$pending !== null) {
            return (self::$pending)($work, $available);
        }
        if (in_array($work->status, ['failed', 'recovery_required'], true)) {
            return new WorkDecision('refuse', 'reconciliation_required');
        }

        return $work->cancellationRequested ? new WorkDecision('cancel', 'cancelled_by_owner') : ($available ? new WorkDecision('run', 'ready') : new WorkDecision('retry', 'waiting_for_health'));
    }

    public static function checkpoint(WorkStatus $work): WorkDecision
    {
        return self::$checkpoint !== null ? (self::$checkpoint)($work) : ($work->cancellationRequested ? new WorkDecision('cancel', 'safe_stop') : new WorkDecision('run', 'continue'));
    }

    /** @param array<array-key, mixed> $payload */
    public static function handle(WorkContext $context, array $payload): void
    {
        self::$executed++;
        if (self::$handle !== null) {
            (self::$handle)($context, $payload);
        }
    }
}
