<?php

namespace Tests\Fixtures;

use App\Support\Seeding\SeedCategory;

class DemoSeed extends FixtureSeed
{
    public static function category(): SeedCategory
    {
        return SeedCategory::Development;
    }

    public static function dependsOn(): array
    {
        return [ReferenceSeed::class];
    }
}
