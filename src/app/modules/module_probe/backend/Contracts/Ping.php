<?php

namespace App\Modules\ModuleProbe\Contracts;

interface Ping
{
    public function read(): PingResult;
}
