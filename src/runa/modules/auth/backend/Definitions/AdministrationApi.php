<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Auth\AdministrationProxy;
use Runa\Modules\Auth\Contracts\AuthAdministration;
use Runa\Modules\Auth\Queries\Administration;

final class AdministrationApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(AuthAdministration::class, AdministrationProxy::class, Administration::class, '1.0.0', [
            'users' => new ApiOperation('administration.users', 'runa.auth.users.read'),
            'profile' => new ApiOperation('administration.profile', 'runa.auth.profiles.read'),
            'lifecycle' => new ApiOperation('administration.lifecycle', 'runa.auth.users.read'),
        ]);
    }
}
