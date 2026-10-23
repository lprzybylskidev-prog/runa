<?php

namespace Tests\Fixtures;

use RuntimeException;

final class UpgradeCalls
{
    /** @var list<string> */
    public static array $calls = [];

    public static ?string $fail = null;

    public static ?string $interrupt = null;

    public static function run(string $step): void
    {
        self::$calls[] = $step;
        if (self::$interrupt === $step) {
            exit(23);
        }
        if (self::$fail === $step) {
            throw new RuntimeException('Injected private failure');
        }
    }
}
