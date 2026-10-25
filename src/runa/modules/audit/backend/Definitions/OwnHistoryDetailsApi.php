<?php

namespace Runa\Modules\Audit\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Audit\Contracts\OwnHistoryDetails;
use Runa\Modules\Audit\OwnHistoryDetailsProxy;
use Runa\Modules\Audit\Queries\ReadOwnHistoryDetails;

final class OwnHistoryDetailsApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(OwnHistoryDetails::class, OwnHistoryDetailsProxy::class, ReadOwnHistoryDetails::class, '1.0.0', [
            'detail' => new ApiOperation('audit.own_history_detail'),
        ]);
    }
}
