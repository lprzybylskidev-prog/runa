<?php

namespace Tests\Fixtures;

use Closure;
use Runa\Contracts\Modules\SettingChange;
use Runa\Contracts\Modules\SettingsContext;
use Runa\Contracts\Modules\SettingsObserver;

final class SettingsCalls implements SettingsObserver
{
    public static ?Closure $apply = null;

    public static ?Closure $compensate = null;

    public static ?Closure $observe = null;

    public static bool $invalidDefault = false;

    /** @var list<SettingsContext> */
    public static array $contexts = [];

    /** @var list<SettingChange> */
    public static array $changes = [];

    public function changed(SettingChange $change): void
    {
        self::$changes[] = $change;
        if (self::$observe !== null) {
            (self::$observe)($change);
        }
    }

    public static function reset(): void
    {
        self::$apply = self::$compensate = self::$observe = null;
        self::$contexts = self::$changes = [];
        self::$invalidDefault = false;
    }
}
