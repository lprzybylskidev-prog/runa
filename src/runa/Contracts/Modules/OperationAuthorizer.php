<?php

namespace Runa\Contracts\Modules;

/** Resolve identity from a trusted boundary; availability never grants permission. */
interface OperationAuthorizer
{
    public function allows(OperationContext $context, string $permission): bool;
}
