<?php

namespace App\Modules\Reference\Http;

use App\Modules\Reference\Contracts\Ping;
use Runa\Support\Modules\Api\ModuleApis;

final readonly class PingController
{
    public function __construct(private ModuleApis $apis) {}

    public function __invoke(): PingResource
    {
        $result = $this->apis->resolve('app.reference', Ping::class, '1.0.0')->read();

        return new PingResource($result);
    }
}
