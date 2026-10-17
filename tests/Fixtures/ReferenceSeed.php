<?php

namespace Tests\Fixtures;

use App\Support\Seeding\SeedCategory;

class ReferenceSeed extends FixtureSeed
{
    public static function category(): SeedCategory
    {
        return SeedCategory::Production;
    }
}
