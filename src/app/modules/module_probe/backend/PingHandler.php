<?php

namespace App\Modules\ModuleProbe;

use App\Modules\ModuleProbe\Contracts\Ping;
use App\Modules\ModuleProbe\Contracts\PingResult;

final class PingHandler implements Ping
{
    public function read(): PingResult
    {
        return new PingResult;
    }
}
