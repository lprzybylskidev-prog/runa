<?php

namespace Runa\Contracts\Modules;

final readonly class TransitionContext
{
    public function __construct(public string $operationId, public string $moduleId, public string $intent, public int $generation, public LifecyclePlan $plan) {}
}
