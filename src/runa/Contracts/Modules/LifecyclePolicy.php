<?php

namespace Runa\Contracts\Modules;

/** Trusted module-owned policy. Hooks must be reversible and idempotent by operation ID. */
interface LifecyclePolicy
{
    public function schemaVersion(): int;

    public function plan(string $intent): LifecyclePlan;

    /** Explicit compatibility decision; null is permitted only for modules requiring no installed data. */
    public function acceptsDataVersion(?string $installedVersion, string $sourceVersion): bool;

    /** Called again with the same operation/token to poll a waiting preparation. */
    public function prepare(TransitionContext $context, ?string $token): Preparation;

    /** A null token includes an interrupted call: reconcile by operation ID, never assume no effects. */
    public function compensate(TransitionContext $context, ?string $token): bool;
}
