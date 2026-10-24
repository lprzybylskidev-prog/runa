<?php

namespace Runa\Support\Seeding;

use Runa\Database\DatabaseScope;
use RuntimeException;

final class SeedGraph
{
    /** @param list<class-string<ManagedSeeder>> $classes
     * @param  array<class-string<ManagedSeeder>, list<class-string<ManagedSeeder>>>  $contributorDependencies
     */
    public function __construct(private readonly array $classes, private readonly array $contributorDependencies = []) {}

    /** @return list<class-string<ManagedSeeder>> */
    public static function registered(): array
    {
        $classes = config('seeding.register', []);
        if (! is_array($classes)) {
            throw new RuntimeException('Seeder registration must be a list.');
        }
        foreach ($classes as $class) {
            if (is_string($class)) {
                app(DatabaseScope::class)->seeder($class);
            }
            if (! is_string($class) || ! is_subclass_of($class, ManagedSeeder::class)) {
                throw new RuntimeException('Every registered seeder must extend ManagedSeeder.');
            }
        }

        return array_values(array_unique($classes));
    }

    /** @param class-string<ManagedSeeder> $class
     * @return list<class-string<ManagedSeeder>>
     */
    public function dependencies(string $class): array
    {
        return array_values(array_unique([...$class::dependsOn(), ...($this->contributorDependencies[$class] ?? [])]));
    }

    /** @return list<class-string<ManagedSeeder>> */
    public function plan(string $mode, string $environment, ?string $scenario = null): array
    {
        if (! in_array($mode, ['default', 'production', 'development', 'occasional', 'graph'], true)) {
            throw new RuntimeException('Unknown seeding mode.');
        }
        $development = in_array($environment, ['local', 'testing'], true);
        if ($mode === 'development' && ! $development) {
            throw new RuntimeException('Development seeders require local or testing environment.');
        }
        $classes = $this->classes;
        sort($classes);
        $state = [];
        $ordered = [];
        $visit = function (string $class) use (&$visit, &$state, &$ordered, $classes): void {
            if (! in_array($class, $classes, true)) {
                throw new RuntimeException('Missing seeder dependency: '.$class);
            }
            if (($state[$class] ?? 0) === 1) {
                throw new RuntimeException('Seeder dependency cycle: '.$class);
            }
            if (($state[$class] ?? 0) === 2) {
                return;
            }
            $state[$class] = 1;
            $dependencies = $this->dependencies($class);
            sort($dependencies);
            foreach ($dependencies as $dependency) {
                if (! in_array($dependency, $classes, true)) {
                    throw new RuntimeException('Missing seeder dependency: '.$dependency);
                }
                if ($class::category() === SeedCategory::Production && $dependency::category() !== SeedCategory::Production) {
                    throw new RuntimeException('Production seeders may only depend on production seeders.');
                }
                if ($class::category() === SeedCategory::Development && $dependency::category() === SeedCategory::Occasional) {
                    throw new RuntimeException('Automatic seeders cannot depend on occasional seeders.');
                }
                $visit($dependency);
            }
            $state[$class] = 2;
            $ordered[] = $class;
        };
        // Validate the complete graph before executing any side effects.
        foreach ($classes as $class) {
            $visit($class);
        }
        if ($mode === 'graph') {
            return $ordered;
        }
        $state = [];
        $ordered = [];
        if ($mode === 'occasional') {
            $matches = array_values(array_filter($classes, fn (string $class): bool => $class === $scenario || class_basename($class) === $scenario));
            if (count($matches) !== 1 || $matches[0]::category() !== SeedCategory::Occasional) {
                throw new RuntimeException('Choose one registered occasional seeder by its unique name.');
            }
            $visit($matches[0]);
        } else {
            foreach ([SeedCategory::Production, SeedCategory::Development] as $category) {
                if ($category === SeedCategory::Development && (! $development || $mode === 'production')) {
                    continue;
                }
                foreach ($classes as $class) {
                    if ($class::category() === $category) {
                        $visit($class);
                    }
                }
            }
        }
        if (! $development && array_any($ordered, fn (string $class): bool => $class::category() === SeedCategory::Development)) {
            throw new RuntimeException('Development dependencies are forbidden outside local/testing.');
        }

        return $ordered;
    }
}
