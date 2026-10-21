<?php

namespace Tests\Fixtures;

class CycleSeed extends ReferenceSeed
{
    public static function dependsOn(): array
    {
        return [self::class];
    }
}
