<?php

namespace Runa\Modules\Audit\Contracts;

/** Server-owned binding; future Auth may provide a safe historical display label. */
interface AuditActorResolver
{
    public function resolve(): AuditActor;
}
