<?php

namespace App\Modules\Reference\Contracts;

interface Ping
{
    public function read(): PingResult;
}
