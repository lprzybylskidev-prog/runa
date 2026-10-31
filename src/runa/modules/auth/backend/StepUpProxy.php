<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthStepUp;
use Runa\Support\Modules\Api\GuardedApi;

final class StepUpProxy extends GuardedApi implements AuthStepUp
{
    public function assertActive(string $action, string $requirement, int $ttlSeconds = 300): bool
    {
        /** @var bool $result */
        $result = $this->invoke(__FUNCTION__, [$action, $requirement, $ttlSeconds]);

        return $result;
    }

    public function activity(string $action, string $requirement, int $ttlSeconds = 300, bool $renew = false): array
    {
        /** @var array{remaining_seconds: float, heartbeat_seconds: float} $result */
        $result = $this->invoke(__FUNCTION__, [$action, $requirement, $ttlSeconds, $renew]);

        return $result;
    }

    public function assertVerified(string $action, string $requirement, int $ttlSeconds = 300, bool $everyAction = false): bool
    {
        /** @var bool $result */
        $result = $this->invoke(__FUNCTION__, [$action, $requirement, $ttlSeconds, $everyAction]);

        return $result;
    }
}
