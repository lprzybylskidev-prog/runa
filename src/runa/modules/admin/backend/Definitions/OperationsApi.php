<?php

namespace Runa\Modules\Admin\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Admin\Contracts\AdminOperations;
use Runa\Modules\Admin\OperationsProxy;
use Runa\Modules\Admin\Queries\Operations;

final class OperationsApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(AdminOperations::class, OperationsProxy::class, Operations::class, '1.0.0', [
            'read' => new ApiOperation('operations.read', 'runa.admin.enter'),
            'execute' => new ApiOperation('operations.execute', 'runa.admin.enter'),
        ]);
    }
}
