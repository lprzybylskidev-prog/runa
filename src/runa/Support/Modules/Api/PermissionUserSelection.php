<?php

namespace Runa\Support\Modules\Api;

use Runa\Modules\Auth\Contracts\BulkUserSelection;
use Runa\Modules\Auth\Contracts\SessionSelection;
use Runa\Modules\Permissions\Contracts\PermissionsSelections;
use Runa\Modules\Permissions\Contracts\UserSelection;

final readonly class PermissionUserSelection implements BulkUserSelection
{
    public function __construct(private ModuleApis $apis) {}

    public function authorize(SessionSelection $selection): void
    {
        $this->apis->resolve('runa.permissions', PermissionsSelections::class, '1.0.0')
            ->users(new UserSelection($selection->publicIds, $selection->all));
    }
}
