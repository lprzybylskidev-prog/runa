<?php

namespace Tests\Fixtures;

use Runa\Support\Seeding\SeedCategory;

class ScenarioSeed extends FixtureSeed
{
    public static function category(): SeedCategory
    {
        return SeedCategory::Occasional;
    }

    public static function dependsOn(): array
    {
        return [DemoSeed::class];
    }
}
