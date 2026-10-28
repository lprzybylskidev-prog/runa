<?php

namespace Runa\Integration\Core;

use Illuminate\Http\Request;
use Runa\Contracts\Modules\HttpAccess;
use Runa\Contracts\Modules\HttpAccountPolicy;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Support\Modules\Api\OperationRejected;

final class AnonymousAccountPolicy implements HttpAccountPolicy
{
    public function principal(Request $request, ?string $module, string $operation, HttpAccess $access): ?TrustedPrincipal
    {
        if ($access !== HttpAccess::Public) {
            throw new OperationRejected('authentication_required', $module ?? 'application', $operation, 401);
        }

        return null;
    }
}
