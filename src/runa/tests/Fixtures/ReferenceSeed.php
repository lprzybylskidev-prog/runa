<?php

namespace Tests\Fixtures;

use Runa\Support\Seeding\SeedCategory;

class ReferenceSeed extends FixtureSeed
{
    public static function category(): SeedCategory
    {
        return SeedCategory::Production;
    }
}
