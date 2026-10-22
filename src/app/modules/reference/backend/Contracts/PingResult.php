<?php

namespace App\Modules\Reference\Contracts;

final readonly class PingResult
{
    public function __construct(public string $message = 'reference') {}
}
