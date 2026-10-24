<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Auth\Actions\BulkSessions;
use Runa\Modules\Auth\BulkSessionsProxy;
use Runa\Modules\Auth\Contracts\AuthBulkSessions;

final class BulkSessionsApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(AuthBulkSessions::class, BulkSessionsProxy::class, BulkSessions::class, '1.0.0', [
            'preview' => new ApiOperation('sessions.bulk_preview', 'runa.auth.sessions.bulk_revoke'),
            'revoke' => new ApiOperation('sessions.bulk_revoke', 'runa.auth.sessions.bulk_revoke'),
        ]);
    }
}
