<?php

namespace Runa\Contracts\Modules;

final readonly class TransitionResult
{
    public int $schemaVersion;

    /** @param array<string, string> $modules */
    public function __construct(public string $operationId, public string $status, public ?int $generation, public array $modules)
    {
        $this->schemaVersion = 1;
    }
}
