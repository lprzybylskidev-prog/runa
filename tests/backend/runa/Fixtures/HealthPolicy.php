<?php

namespace Tests\Fixtures;

use Runa\Contracts\Modules\HealthCheck;
use Runa\Contracts\Modules\HealthContext;
use Runa\Contracts\Modules\HealthDefinition;

class HealthPolicy implements \Runa\Contracts\Modules\HealthPolicy
{
    public const ID = '';

    /** @var array<string, array{mode?: string, severity?: string, operations?: list<string>, interval?: int, expiry?: int}> */
    public static array $definitions = [];

    /** @var array<string, callable> */
    public static array $checks = [];

    /** @var array<string, int> */
    public static array $calls = [];

    public function schemaVersion(): int
    {
        return 2;
    }

    public function definition(): HealthDefinition
    {

        $definition = self::$definitions[static::ID] ?? [];

        return new HealthDefinition('v1', $definition['mode'] ?? 'on_use', [new HealthCheck('connection', $definition['severity'] ?? 'critical', 1, $definition['interval'] ?? 10, $definition['expiry'] ?? 20, $definition['operations'] ?? [])]);
    }

    public function check(string $check, HealthContext $context): bool
    {
        self::$calls[static::ID] = (self::$calls[static::ID] ?? 0) + 1;

        return isset(self::$checks[static::ID]) ? (self::$checks[static::ID])($context) : true;
    }
}
