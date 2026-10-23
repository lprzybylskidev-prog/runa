<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Auth\Actions\ManageSecurity;
use Runa\Modules\Auth\Contracts\AuthSecurity;
use Runa\Modules\Auth\SecurityProxy;

final class SecurityApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(AuthSecurity::class, SecurityProxy::class, ManageSecurity::class, '1.0.0', [
            'requestPasswordReset' => new ApiOperation('password.admin_reset', 'runa.auth.passwords.reset'),
            'resetFactors' => new ApiOperation('two_factor.admin_reset', 'runa.auth.two_factor.reset'),
            'trustedDevices' => new ApiOperation('trust.list', 'runa.auth.trust.manage'),
            'revokeDevice' => new ApiOperation('trust.revoke', 'runa.auth.trust.manage'),
        ]);
    }
}
