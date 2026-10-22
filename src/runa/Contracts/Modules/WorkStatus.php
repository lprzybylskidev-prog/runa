<?php

namespace Runa\Contracts\Modules;

/** Safe management snapshot; no payload, exception, executable class or private lease token. */
final readonly class WorkStatus
{
    public int $schemaVersion;

    public function __construct(public string $workId, public string $moduleId, public string $operation, public string $status, public int $generation, public int $currentGeneration, public int $attempts, public bool $cancellationRequested, public ?string $leaseUntil, public string $progress, public string $correlationId, public ?bool $cancellable = null, public ?string $limitations = null)
    {
        $this->schemaVersion = 1;
    }
}
