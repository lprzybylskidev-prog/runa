<?php

namespace Runa\Modules\Permissions\Contracts;

interface Permissions
{
    /** Current trusted principal only. Never a client-supplied actor identifier. */
    public function allows(string $permission): bool;

    public function explain(string $userPublicId, ?string $after = null): PermissionPage;
}
