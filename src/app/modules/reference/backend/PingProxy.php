<?php

namespace App\Modules\Reference;

use App\Modules\Reference\Contracts\Ping;
use App\Modules\Reference\Contracts\PingResult;
use Runa\Support\Modules\Api\GuardedApi;

final class PingProxy extends GuardedApi implements Ping
{
    public function read(): PingResult
    {
        $result = $this->invoke(__FUNCTION__, []);
        if (! $result instanceof PingResult) {
            throw new \LogicException('Unexpected reference response contract.');
        }

        return $result;
    }
}
