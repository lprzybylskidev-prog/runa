<?php

namespace Runa\Modules\Auth\Contracts;

/** Server-owned requirements only. Call within the business transaction, before mutation. */
interface AuthStepUp
{
    public function assertActive(string $action, string $requirement, int $ttlSeconds = 300): bool;

    /** @return array{remaining_seconds: float, heartbeat_seconds: float} */
    public function activity(string $action, string $requirement, int $ttlSeconds = 300, bool $renew = false): array;

    public function assertVerified(string $action, string $requirement, int $ttlSeconds = 300, bool $everyAction = false): bool;
}
