<?php

namespace Runa\Modules\Audit;

use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Contracts\AuditRecords;
use Runa\Modules\Audit\Contracts\AuditReference;
use Runa\Support\Modules\Api\GuardedApi;

final class AuditProxy extends GuardedApi implements AuditRecords
{
    public function record(AuditEvent $event): AuditReference
    {
        return $this->invoke(__FUNCTION__, [$event]);
    }

    public function attempt(AuditEvent $event): AuditReference
    {
        return $this->invoke(__FUNCTION__, [$event]);
    }
}
