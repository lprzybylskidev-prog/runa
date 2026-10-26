<?php

namespace Runa\Modules\Audit;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Storage\AppendEvents;

final class InitializeAudit implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        app(AppendEvents::class)->record($context->connection, new AuditEvent('runa.audit', 'audit.store_ready'));
    }
}
