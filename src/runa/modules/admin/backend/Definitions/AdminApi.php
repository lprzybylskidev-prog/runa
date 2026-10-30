<?php

namespace Runa\Modules\Admin\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Admin\AccessProxy;
use Runa\Modules\Admin\Contracts\AdminAccess;
use Runa\Modules\Admin\Queries\Access;

final class AdminApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(AdminAccess::class, AccessProxy::class, Access::class, '1.0.0', [
            'bootstrap' => new ApiOperation('bootstrap', 'runa.admin.enter'),
            'page' => new ApiOperation('page.read', 'runa.admin.enter'),
            'read' => new ApiOperation('section.read', 'runa.admin.enter'),
            'preview' => new ApiOperation('preview', 'runa.admin.enter'),
            'save' => new ApiOperation('save', 'runa.admin.enter'),
            'command' => new ApiOperation('command', 'runa.admin.enter'),
        ]);
    }
}
