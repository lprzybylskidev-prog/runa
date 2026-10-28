<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthStepUp;
use Runa\Support\Modules\Api\GuardedApi;

final class StepUpProxy extends GuardedApi implements AuthStepUp
{
    public function assertVerified(string $action, string $requirement, int $ttlSeconds = 300, bool $everyAction = false): bool
    {
        /** @var bool $result */
        $result = $this->invoke(__FUNCTION__, [$action, $requirement, $ttlSeconds, $everyAction]);

        return $result;
    }
}
