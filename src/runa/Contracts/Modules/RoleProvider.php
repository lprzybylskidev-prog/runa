<?php

namespace Runa\Contracts\Modules;

/** Optional extension in the existing management declaration category. */
interface RoleProvider extends ManagementProvider
{
    /** @return list<RoleDefinition> */
    public function roles(): array;
}
