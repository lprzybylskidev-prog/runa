<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Auth\Contracts\AuthIdentities;
use Runa\Modules\Auth\IdentityProxy;
use Runa\Modules\Auth\Queries\LookupIdentity;

final class IdentityApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(AuthIdentities::class, IdentityProxy::class, LookupIdentity::class, '1.0.0', [
            'identity' => new ApiOperation('identity.read', 'runa.auth.identities.read'),
            'eligibility' => new ApiOperation('eligibility.read', 'runa.auth.eligibility.read'),
        ]);
    }
}
