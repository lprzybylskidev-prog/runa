<?php

namespace Tests\Fixtures;

use Runa\Contracts\Modules\HealthCheck;
use Runa\Contracts\Modules\HealthContext;
use Runa\Contracts\Modules\HealthDefinition;
use RuntimeException;

class HealthPolicy implements \Runa\Contracts\Modules\HealthPolicy
{
    public const ID = '';

    /** @var array<string, array{mode?: string, severity?: string, operations?: list<string>}> */
    public static array $definitions = [];

    /** @var array<string, callable> */
    public static array $checks = [];

    /** @var array<string, int> */
    public static array $calls = [];

    public function schemaVersion(): int
    {
        return 1;
    }

    public function definition(array $overrides): HealthDefinition
    {
        if (array_diff(array_keys($overrides), ['mode', 'interval', 'expiry']) !== [] || (isset($overrides['mode']) && ! in_array($overrides['mode'], ['on_use', 'scheduled', 'hybrid'], true)) || (isset($overrides['interval']) && ! is_int($overrides['interval'])) || (isset($overrides['expiry']) && ! is_int($overrides['expiry']))) {
            throw new RuntimeException('Forbidden override.');
        }
        $definition = self::$definitions[static::ID] ?? [];

        return new HealthDefinition('v1', $overrides['mode'] ?? $definition['mode'] ?? 'on_use', [new HealthCheck('connection', $definition['severity'] ?? 'critical', 1, $overrides['interval'] ?? 10, $overrides['expiry'] ?? 20, $definition['operations'] ?? [])]);
    }

    public function check(string $check, HealthContext $context): bool
    {
        self::$calls[static::ID] = (self::$calls[static::ID] ?? 0) + 1;

        return isset(self::$checks[static::ID]) ? (self::$checks[static::ID])($context) : true;
    }
}
