<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Auth\Actions\ChangeIdentity;
use Runa\Modules\Auth\Contracts\AuthLifecycle;
use Runa\Modules\Auth\LifecycleProxy;

final class LifecycleApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(AuthLifecycle::class, LifecycleProxy::class, ChangeIdentity::class, '1.0.0', [
            'create' => new ApiOperation('identity.create', 'runa.auth.users.create'),
            'change' => new ApiOperation('identity.change', 'runa.auth.users.manage'),
        ]);
    }
}
