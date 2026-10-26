<?php

namespace Runa\Modules\Audit\Definitions;

use Runa\Contracts\Modules\LifecyclePlan;
use Runa\Contracts\Modules\LifecyclePolicy;
use Runa\Contracts\Modules\Preparation;
use Runa\Contracts\Modules\TransitionContext;

final class AuditLifecycle implements LifecyclePolicy
{
    public function schemaVersion(): int
    {
        return 1;
    }

    public function plan(string $intent): LifecyclePlan
    {
        return new LifecyclePlan('audit:1');
    }

    public function acceptsDataVersion(?string $installedVersion, string $sourceVersion): bool
    {
        return $installedVersion === $sourceVersion;
    }

    public function prepare(TransitionContext $context, ?string $token): Preparation
    {
        return new Preparation('ready');
    }

    public function compensate(TransitionContext $context, ?string $token): bool
    {
        return true;
    }
}
