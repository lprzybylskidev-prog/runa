<?php

namespace App\Modules\ModuleProbe\Definitions;

use App\Modules\ModuleProbe\Http\PingController;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\HttpEndpoint;
use Runa\Contracts\Modules\HttpProvider;

final class ProbeHttp implements HttpProvider
{
    public function schemaVersion(): int
    {
        return 1;
    }

    public function endpoints(): array
    {
        return [new HttpEndpoint('GET', 'ping', new ApiOperation('probe.read'), PingController::class)];
    }
}
