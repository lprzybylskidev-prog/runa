<?php

namespace Tests\Fixtures;

use App\Support\Seeding\ManagedSeeder;
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
