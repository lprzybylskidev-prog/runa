<?php

namespace Runa\Support\Modules\Work;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/** Only this framework envelope is serialized; owned handlers resolve after admission. */
final class ModuleJob implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 0; // Durable module attempts, including availability releases, impose the bound.

    public int $maxExceptions = 3; // Bound infrastructure exceptions independently of module releases.

    public int $backoff = 5;

    public bool $failOnTimeout = true;

    public bool $afterCommit = false; // Work row and database envelope share the caller transaction.

    public function __construct(public readonly string $workId, public readonly int $timeout) {}

    public function handle(ModuleWork $work): void
    {
        if ($this->job !== null && ($this->job->payload()['runa_work'] ?? null) !== $this->workId) {
            throw new \RuntimeException('Module queue envelope identity mismatch.');
        }
        $delay = $work->execute($this->workId);
        if ($delay !== null) {
            $this->release($delay);
        }
    }
}
