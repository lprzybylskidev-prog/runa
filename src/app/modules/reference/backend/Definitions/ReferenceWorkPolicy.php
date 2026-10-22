<?php

namespace App\Modules\Reference\Definitions;

use Runa\Contracts\Modules\LifecyclePlan;
use Runa\Contracts\Modules\LifecyclePolicy;
use Runa\Contracts\Modules\Preparation;
use Runa\Contracts\Modules\TransitionContext;
use Runa\Support\Modules\Work\ModuleWork;

/** Reference module drains its declared work before administrative publication. */
final readonly class ReferenceWorkPolicy implements LifecyclePolicy
{
    public function schemaVersion(): int
    {
        return 1;
    }

    public function plan(string $intent): LifecyclePlan
    {
        return new LifecyclePlan('reference:1');
    }

    public function acceptsDataVersion(?string $installedVersion, string $sourceVersion): bool
    {
        return $installedVersion === $sourceVersion;
    }

    public function prepare(TransitionContext $context, ?string $token): Preparation
    {
        return new Preparation(app(ModuleWork::class)->active($context->moduleId) === [] ? 'ready' : 'waiting', code: 'reference_draining');
    }

    public function compensate(TransitionContext $context, ?string $token): bool
    {
        return true;
    }
}
