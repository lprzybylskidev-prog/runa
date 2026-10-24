<?php

namespace Runa\Modules\Permissions\Contracts;

/** Fixed development scenarios only; not a general grant/authority writer. */
interface DevelopmentAccess
{
    public function populate(): void;
}
