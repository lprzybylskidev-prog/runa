<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Auth\Actions\BulkUsers;
use Runa\Modules\Auth\BulkUsersProxy;
use Runa\Modules\Auth\Contracts\AuthBulkUsers;

final class BulkUsersApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(AuthBulkUsers::class, BulkUsersProxy::class, BulkUsers::class, '1.0.0', [
            'preview' => new ApiOperation('users.bulk_preview', 'runa.auth.users.read'),
            'execute' => new ApiOperation('users.bulk_execute', 'runa.auth.users.read'),
        ]);
    }
}
