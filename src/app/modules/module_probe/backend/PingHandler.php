<?php

namespace App\Modules\ModuleProbe;

use App\Modules\ModuleProbe\Contracts\Ping;
use App\Modules\ModuleProbe\Contracts\PingResult;
use Illuminate\Support\Facades\Log;

final class PingHandler implements Ping
{
    public function read(): PingResult
    {
        Log::info('Probe public API observed.', ['code' => 'probe_read']);

        return new PingResult;
    }
}
