<?php

namespace Runa\Support\Modules\Api;

use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\OperationContext;

final class DenyProtectedOperations implements OperationAuthorizer
{
    public function allows(OperationContext $context, string $permission): bool
    {
        return false;
    }
}
