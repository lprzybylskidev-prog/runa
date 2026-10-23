<?php

namespace Runa\Contracts\Modules;

final readonly class OperationContext
{
    public int $schemaVersion;

    public function __construct(public string $moduleId, public string $operation, public string $operationId, public string $correlationId, public int $generation, public ?string $originModule = null)
    {
        $this->schemaVersion = 1;
    }
}
