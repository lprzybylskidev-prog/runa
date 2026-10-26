<?php

namespace Runa\Modules\Permissions\Contracts;

interface PermissionsSelections
{
    /** Authorizes selection intent, never a durable grant or proof for later execution. */
    public function users(UserSelection $selection): UserSelection;
}
