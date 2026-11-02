<?php

namespace Runa\Modules\Auth\Support;

use Runa\Modules\Auth\Contracts\LifecycleChange;
use Runa\Modules\Auth\Models\User;

/** Private in-memory owning phase; never transported through a public API. */
final readonly class LifecyclePlan
{
    /** @param array<string,bool|int|string|null> $before */
    public function __construct(public User $user, public array $before, public LifecycleChange $change, public bool $revoke) {}
}
