<?php

namespace Runa\Modules\Audit\Contracts;

/** Guarded runtime API; actor/time/connection are always server-owned. */
interface AuditRecords
{
    public function record(AuditEvent $event): AuditReference;

    public function attempt(AuditEvent $event): AuditReference;
}
