<?php

namespace App\Modules\Reference\Models;

use Runa\Support\Modules\Data\ModuleModel;

/** Development example of an owned UUIDv7 entity. */
final class Sample extends ModuleModel
{
    protected $table = 'app_reference.samples';

    protected $fillable = ['code'];

    public static function moduleId(): string
    {
        return 'app.reference';
    }
}
