<?php

namespace Tests\Fixtures;

use App\Support\Seeding\SeedCategory;

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
