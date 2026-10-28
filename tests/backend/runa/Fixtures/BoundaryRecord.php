<?php

namespace Tests\Fixtures;

use Runa\Support\Modules\Data\ModuleModel;

class BoundaryRecord extends ModuleModel
{
    protected $table = 'app_boundary.records';

    protected $guarded = [];

    public static function moduleId(): string
    {
        return 'app.boundary';
    }
}
