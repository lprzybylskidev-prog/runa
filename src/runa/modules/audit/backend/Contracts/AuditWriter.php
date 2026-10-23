<?php

namespace Runa\Modules\Audit\Contracts;

use Illuminate\Database\Connection;

/** Local transaction participation contract, never passed through ModuleApis. */
interface AuditWriter
{
    public function record(Connection $connection, AuditEvent $event): AuditReference;
}
