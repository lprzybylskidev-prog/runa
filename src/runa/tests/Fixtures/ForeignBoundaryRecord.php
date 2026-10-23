<?php

namespace Tests\Fixtures;

class ForeignBoundaryRecord extends BoundaryRecord
{
    protected $table = 'runa_boundary.records';

    public static function moduleId(): string
    {
        return 'runa.boundary';
    }
}
