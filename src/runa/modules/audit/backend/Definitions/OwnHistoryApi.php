<?php

namespace Runa\Modules\Audit\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Audit\Contracts\OwnHistory;
use Runa\Modules\Audit\OwnHistoryProxy;
use Runa\Modules\Audit\Queries\ReadOwnHistory;

final class OwnHistoryApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(OwnHistory::class, OwnHistoryProxy::class, ReadOwnHistory::class, '1.0.0', [
            'page' => new ApiOperation('audit.own_history'),
        ]);
    }
}
