<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Auth\Actions\Policies;
use Runa\Modules\Auth\Contracts\AuthPolicies;
use Runa\Modules\Auth\PoliciesProxy;

final class PoliciesApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(AuthPolicies::class, PoliciesProxy::class, Policies::class, '1.1.0', [
            'exceptionVersions' => new ApiOperation('policy.exception_versions', 'runa.auth.policy.exceptions'),
            'exceptions' => new ApiOperation('policy.exceptions', 'runa.auth.policy.exceptions'),
            'history' => new ApiOperation('policy.history', 'runa.auth.policy.read'),
            'preview' => new ApiOperation('policy.preview', 'runa.auth.policy.write'),
            'publish' => new ApiOperation('policy.publish', 'runa.auth.policy.write'),
            'restore' => new ApiOperation('policy.restore', 'runa.auth.policy.write'),
            'revokeExceptions' => new ApiOperation('policy.revoke_exceptions', 'runa.auth.policy.exceptions'),
            'exception' => new ApiOperation('policy.exception', 'runa.auth.policy.exceptions'),
        ]);
    }
}
