<?php

namespace Tests\Fixtures;

use Closure;
use Runa\Contracts\Modules\LifecyclePlan;
use Runa\Contracts\Modules\LifecyclePolicy;
use Runa\Contracts\Modules\Preparation;
use Runa\Contracts\Modules\TransitionContext;

class TransitionPolicy implements LifecyclePolicy
{
    public const ID = '';

    /** @var array<string, Closure> */
    public static array $prepare = [];

    /** @var array<string, Closure> */
    public static array $compensate = [];

    /** @var array<string, string> */
    public static array $revisions = [];

    /** @var list<string> */
    public static array $calls = [];

    public function schemaVersion(): int
    {
        return 1;
    }

    public function plan(string $intent): LifecyclePlan
    {
        return new LifecyclePlan(self::$revisions[static::ID] ?? '1');
    }

    public function acceptsDataVersion(?string $installedVersion, string $sourceVersion): bool
    {
        return $installedVersion === null || $installedVersion === $sourceVersion;
    }

    public function prepare(TransitionContext $context, ?string $token): Preparation
    {
        self::$calls[] = 'prepare:'.static::ID;

        return isset(self::$prepare[static::ID]) ? (self::$prepare[static::ID])($context, $token) : new Preparation('ready', 'private-token');
    }

    public function compensate(TransitionContext $context, ?string $token): bool
    {
        self::$calls[] = 'compensate:'.static::ID;

        return isset(self::$compensate[static::ID]) ? (self::$compensate[static::ID])($context, $token) : true;
    }
}
