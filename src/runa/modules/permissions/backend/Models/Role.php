<?php

namespace Runa\Modules\Permissions\Models;

use Runa\Support\Modules\Data\ModuleModel;

/** Persistence belongs to audited Permissions Actions; never mutate through foreign modules. */
final class Role extends ModuleModel
{
    protected $table = 'runa_permissions.roles';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['system' => 'boolean', 'version' => 'integer'];
    }

    public static function moduleId(): string
    {
        return 'runa.permissions';
    }
}
