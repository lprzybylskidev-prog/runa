<?php

namespace Runa\Modules\Audit\Contracts;

/** Server-owned binding for optional safe historical actor-label enrichment. */
interface AuditActorResolver
{
    public function resolve(): AuditActor;
}
