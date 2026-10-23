<?php

namespace Runa\Modules\Audit\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Audit\Actions\RecordEvent;
use Runa\Modules\Audit\AuditProxy;
use Runa\Modules\Audit\Contracts\AuditRecords;

final class AuditApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(AuditRecords::class, AuditProxy::class, RecordEvent::class, '1.0.0', [
            'record' => new ApiOperation('audit.record'), 'attempt' => new ApiOperation('audit.attempt'),
        ]);
    }
}
