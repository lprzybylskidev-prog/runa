<?php

namespace Runa\Contracts\Modules;

use Illuminate\Http\Request;

/** Resolve through private current-state identity reads, never a protected module API. */
interface HttpAccountPolicy
{
    public function principal(Request $request, ?string $module, string $operation, HttpAccess $access): ?TrustedPrincipal;
}
