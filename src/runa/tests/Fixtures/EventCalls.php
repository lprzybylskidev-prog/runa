<?php

namespace Tests\Fixtures;

use Closure;

final class EventCalls
{
    /** @var list<array{string, string, string, ?string}> */
    public static array $calls = [];

    public static ?Closure $sync = null;

    public static ?Closure $async = null;

    public static bool $integration = false;

    public static int $constructed = 0;

    public static function reset(): void
    {
        self::$calls = [];
        self::$sync = self::$async = null;
        self::$integration = false;
        self::$constructed = 0;
    }
}
