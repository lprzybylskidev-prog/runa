<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthStepUp;
use Runa\Support\Modules\Api\GuardedApi;

final class StepUpProxy extends GuardedApi implements AuthStepUp
{
    public function assertVerified(string $action, string $requirement, int $ttlSeconds = 300, bool $everyAction = false): bool
    {
        return $this->invoke(__FUNCTION__, [$action, $requirement, $ttlSeconds, $everyAction]);
    }
}
