<?php

namespace Runa\Integration\Core;

use Illuminate\Http\Request;
use Runa\Contracts\Modules\AuthRequestThrottle;

/** The optional Throttling module has not installed its runtime-aware bridge. */
final readonly class NoAuthRequestThrottle implements AuthRequestThrottle
{
    public function enforce(Request $request, string $operation): bool
    {
        return false;
    }
}
