<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthDelivery;
use Runa\Support\Modules\Api\GuardedApi;

final class DeliveryProxy extends GuardedApi implements AuthDelivery
{
    public function recipients(string $publicId, string $module, string $category): array
    {
        /** @var list<string> $result */
        $result = $this->invoke(__FUNCTION__, [$publicId, $module, $category]);

        return $result;
    }
}
