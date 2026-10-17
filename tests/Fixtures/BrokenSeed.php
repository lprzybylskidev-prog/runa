<?php

namespace Tests\Fixtures;

class BrokenSeed extends ReferenceSeed
{
    public static function dependsOn(): array
    {
        return [DemoSeed::class];
    }
}
