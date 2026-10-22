<?php

namespace App\Modules\ModuleProbe\Definitions;

use Runa\Contracts\Modules\LifecyclePlan;
use Runa\Contracts\Modules\LifecyclePolicy;
use Runa\Contracts\Modules\Preparation;
use Runa\Contracts\Modules\TransitionContext;
use Runa\Support\Modules\Work\ModuleWork;

/** Stateless probe drains its declared work before administrative publication. */
final readonly class ProbeWorkPolicy implements LifecyclePolicy
{
    public function schemaVersion(): int
    {
        return 1;
    }

    public function plan(string $intent): LifecyclePlan
    {
        return new LifecyclePlan('stateless:2');
    }

    public function acceptsDataVersion(?string $installedVersion, string $sourceVersion): bool
    {
        return $installedVersion === null || $installedVersion === $sourceVersion;
    }

    public function prepare(TransitionContext $context, ?string $token): Preparation
    {
        return new Preparation(app(ModuleWork::class)->active($context->moduleId) === [] ? 'ready' : 'waiting', code: 'probe_draining');
    }

    public function compensate(TransitionContext $context, ?string $token): bool
    {
        return true;
    }
}
