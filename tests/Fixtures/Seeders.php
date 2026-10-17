<?php

namespace Tests\Fixtures;

use App\Support\Seeding\ManagedSeeder;
use App\Support\Seeding\SeedCategory;
use App\Support\Seeding\SeedContext;

abstract class FixtureSeed extends ManagedSeeder
{
    /** @var list<string> */
    public static array $executed = [];

    protected function seed(SeedContext $context): void
    {
        self::$executed[] = static::class;
    }
}

class ReferenceSeed extends FixtureSeed
{
    public static function category(): SeedCategory
    {
        return SeedCategory::Production;
    }
}

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

class BrokenSeed extends ReferenceSeed
{
    public static function dependsOn(): array
    {
        return [DemoSeed::class];
    }
}

class CycleSeed extends ReferenceSeed
{
    public static function dependsOn(): array
    {
        return [self::class];
    }
}
