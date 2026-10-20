<?php

namespace Tests\Fixtures;

use Runa\Support\Seeding\ManagedSeeder;
use Runa\Support\Seeding\SeedContext;

abstract class FixtureSeed extends ManagedSeeder
{
    /** @var list<string> */
    public static array $executed = [];

    protected function seed(SeedContext $context): void
    {
        self::$executed[] = static::class;
    }
}
