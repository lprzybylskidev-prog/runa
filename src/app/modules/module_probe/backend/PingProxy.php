<?php

namespace App\Modules\ModuleProbe;

use App\Modules\ModuleProbe\Contracts\Ping;
use App\Modules\ModuleProbe\Contracts\PingResult;
use Runa\Support\Modules\Api\GuardedApi;

final class PingProxy extends GuardedApi implements Ping
{
    public function read(): PingResult
    {
        $result = $this->invoke(__FUNCTION__, []);
        if (! $result instanceof PingResult) {
            throw new \LogicException('Unexpected probe response contract.');
        }

        return $result;
    }
}
