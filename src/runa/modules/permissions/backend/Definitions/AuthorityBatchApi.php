<?php

namespace Runa\Modules\Permissions\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Permissions\Actions\ManageAuthority;
use Runa\Modules\Permissions\AuthorityBatchProxy;
use Runa\Modules\Permissions\Contracts\PermissionsAuthorityBatch;

final class AuthorityBatchApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(PermissionsAuthorityBatch::class, AuthorityBatchProxy::class, ManageAuthority::class, '1.0.0', [
            'previewBatch' => new ApiOperation('authority.batch_preview', 'runa.permissions.authority.manage'),
            'applyBatch' => new ApiOperation('authority.batch_apply', 'runa.permissions.authority.manage'),
        ]);
    }
}
