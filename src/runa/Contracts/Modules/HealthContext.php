<?php

namespace Runa\Contracts\Modules;

final readonly class HealthContext
{
    public function __construct(public string $moduleId, public ?string $operation, public string $trigger, public float $deadline) {}
}
