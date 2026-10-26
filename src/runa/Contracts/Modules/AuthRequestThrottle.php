<?php

namespace Runa\Contracts\Modules;

use Illuminate\Http\Request;

/** Trusted optional request-throttling adapter; no credential/proof validation is delegated. */
interface AuthRequestThrottle
{
    /**
     * Recheck module availability on every call. True means exclusively handled;
     * false means no active integration, before any counting/enforcement occurred.
     * Denials/failures throw and must never become false/local fallback.
     */
    public function enforce(Request $request, string $operation): bool;
}
