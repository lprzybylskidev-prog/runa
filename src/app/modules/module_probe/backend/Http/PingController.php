<?php

namespace App\Modules\ModuleProbe\Http;

use App\Modules\ModuleProbe\Contracts\Ping;
use Runa\Support\Modules\Api\ModuleApis;

final readonly class PingController
{
    public function __construct(private ModuleApis $apis) {}

    public function __invoke(): PingResource
    {
        $result = $this->apis->resolve('app.module_probe', Ping::class, '1.0.0')->read();

        return new PingResource($result);
    }
}
