<?php

namespace App\Modules\Reference\Definitions;

use App\Modules\Reference\Http\PingController;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\HttpEndpoint;
use Runa\Contracts\Modules\HttpProvider;

final class ReferenceHttp implements HttpProvider
{
    public function schemaVersion(): int
    {
        return 1;
    }

    public function endpoints(): array
    {
        return [new HttpEndpoint('GET', 'ping', new ApiOperation('reference.read'), PingController::class)];
    }
}
