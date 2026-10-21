<?php

namespace Tests\Fixtures;

use Runa\Support\Seeding\SeedCategory;

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
