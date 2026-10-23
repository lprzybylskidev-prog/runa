<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Auth\Actions\DeliveryRouting;
use Runa\Modules\Auth\Contracts\AuthDelivery;
use Runa\Modules\Auth\DeliveryProxy;

final class DeliveryApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(AuthDelivery::class, DeliveryProxy::class, DeliveryRouting::class, '1.0.0', [
            'recipients' => new ApiOperation('delivery.recipients', 'runa.auth.delivery.resolve'),
        ]);
    }
}
