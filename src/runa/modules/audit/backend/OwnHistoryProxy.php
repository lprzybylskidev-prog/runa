<?php

namespace Runa\Modules\Audit;

use Runa\Modules\Audit\Contracts\OwnHistory;
use Runa\Support\Modules\Api\GuardedApi;

final class OwnHistoryProxy extends GuardedApi implements OwnHistory
{
    public function page(string $module, string $entityType, array $actions, ?string $before = null): array
    {
        /** @var array{entries: list<array{public_id: string, occurred_at: string, action: string, outcome: string}>, next: string|null} $result */
        $result = $this->invoke(__FUNCTION__, [$module, $entityType, $actions, $before]);

        return $result;
    }
}
