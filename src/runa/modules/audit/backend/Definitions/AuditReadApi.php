<?php

namespace Runa\Modules\Audit\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Audit\AuditReadProxy;
use Runa\Modules\Audit\Contracts\AuditReads;
use Runa\Modules\Audit\Queries\ReadEvents;

final class AuditReadApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(AuditReads::class, AuditReadProxy::class, ReadEvents::class, '1.0.0', [
            'page' => new ApiOperation('audit.read.page', 'runa.audit.read'),
            'detail' => new ApiOperation('audit.read.detail', 'runa.audit.read'),
            'metadata' => new ApiOperation('audit.read.metadata', 'runa.audit.read'),
        ]);
    }
}
