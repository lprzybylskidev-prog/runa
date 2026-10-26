<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Auth\Actions\ManageSessions;
use Runa\Modules\Auth\Contracts\AuthSessions;
use Runa\Modules\Auth\SessionsProxy;

final class SessionsApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(AuthSessions::class, SessionsProxy::class, ManageSessions::class, '1.0.0', [
            'listFor' => new ApiOperation('sessions.list', 'runa.auth.sessions.manage'),
            'revoke' => new ApiOperation('sessions.revoke', 'runa.auth.sessions.manage'),
            'revokeAll' => new ApiOperation('sessions.revoke_all', 'runa.auth.sessions.manage'),
        ]);
    }
}
