<?php

namespace App\Modules\ModuleProbe\Contracts;

final readonly class PingResult
{
    public function __construct(public string $message = 'module_probe') {}
}
