<?php

namespace Runa\Support\Seeding;

use Random\Engine\Mt19937;
use Random\Engine\Secure;
use Random\Randomizer;
use RuntimeException;

final class SeedContext
{
    public readonly Randomizer $random;

    /** @param array<array-key, mixed> $options */
    public function __construct(private readonly array $options)
    {
        $random = $options['random'] ?? false;
        $seed = $options['seed'] ?? 1;
        if (! is_bool($random) || ! is_int($seed)) {
            throw new RuntimeException('Seeder random must be boolean and seed must be integer.');
        }
        $this->random = new Randomizer($random ? new Secure : new Mt19937($seed));
    }

    public function count(string $group, int $default = 0): int
    {
        $counts = $this->options['counts'] ?? [];
        if (! is_array($counts)) {
            throw new RuntimeException('Seeder counts must be an array.');
        }
        $count = $counts[$group] ?? $default;
        if (! is_int($count) || $count < 0) {
            throw new RuntimeException('Seeder counts must be non-negative integers.');
        }

        return $count;
    }
}
