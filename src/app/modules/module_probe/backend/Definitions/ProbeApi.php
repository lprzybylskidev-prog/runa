<?php

namespace App\Modules\ModuleProbe\Definitions;

use App\Modules\ModuleProbe\Contracts\Ping;
use App\Modules\ModuleProbe\PingHandler;
use App\Modules\ModuleProbe\PingProxy;
use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;

final class ProbeApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(Ping::class, PingProxy::class, PingHandler::class, '1.0.0', ['read' => new ApiOperation('probe.read')]);
    }
}
