<?php

namespace Runa\Modules\Audit;

use Runa\Modules\Audit\Contracts\OwnHistoryDetails;
use Runa\Support\Modules\Api\GuardedApi;

final class OwnHistoryDetailsProxy extends GuardedApi implements OwnHistoryDetails
{
    public function detail(string $module, string $entityType, string $publicId, array $fieldsByAction): ?array
    {
        return $this->invoke(__FUNCTION__, [$module, $entityType, $publicId, $fieldsByAction]);
    }
}
