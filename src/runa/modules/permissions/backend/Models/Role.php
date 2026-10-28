<?php

namespace Runa\Modules\Permissions\Models;

use Runa\Support\Modules\Data\ModuleModel;

/** Persistence belongs to audited Permissions Actions; never mutate through foreign modules.
 * @property int $id
 * @property string $public_id
 * @property string $code
 * @property string $name_pl
 * @property string $name_en
 * @property string $description_pl
 * @property string $description_en
 * @property string|null $module_id
 * @property bool $system
 * @property int $version
 */
final class Role extends ModuleModel
{
    protected $table = 'runa_permissions.roles';

    protected $guarded = ['*'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['system' => 'boolean', 'version' => 'integer'];
    }

    public static function moduleId(): string
    {
        return 'runa.permissions';
    }
}
