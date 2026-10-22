<?php

namespace App\Modules\Reference\Definitions;

use App\Modules\Reference\Contracts\Ping;
use App\Modules\Reference\PingHandler;
use App\Modules\Reference\PingProxy;
use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;

final class ReferenceApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(Ping::class, PingProxy::class, PingHandler::class, '1.0.0', ['read' => new ApiOperation('reference.read')]);
    }
}
