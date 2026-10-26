<?php

namespace Runa\Modules\Auth\Contracts;

/** Server-owned requirements only. Call within the business transaction, before mutation. */
interface AuthStepUp
{
    public function assertVerified(string $action, string $requirement, int $ttlSeconds = 300, bool $everyAction = false): bool;
}
