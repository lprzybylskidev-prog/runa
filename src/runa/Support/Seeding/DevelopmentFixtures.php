<?php

namespace Runa\Support\Seeding;

use Closure;
use Illuminate\Database\Connection;
use ReflectionClass;
use Runa\Management\DevelopmentContext;
use Runa\Support\Modules\Data\AuthorityLock;
use Runa\Support\Modules\ModuleAvailability;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleIdentity;
use Runa\Support\Modules\ModuleRegistry;
use RuntimeException;

/** Trusted local tooling only. No HTTP, queue or production fixture ingress. */
final class DevelopmentFixtures
{
    public static function assertAllowed(): void
    {
        if (! app()->runningInConsole() || ! app()->environment('local', 'testing')) {
            throw new RuntimeException('Development fixtures require local/testing console execution.');
        }
        DevelopmentContext::assertAllowed(dirname(base_path()));
    }

    public function contributor(string $module): DevelopmentContributor
    {
        self::assertAllowed();
        $identity = new ModuleIdentity($module);
        $classes = config('seeding.contributors', []);
        if (! is_array($classes) || ! is_string($class = $classes[$module] ?? null)) {
            throw new RuntimeException('Choose an explicitly registered module development contributor.');
        }
        $snapshot = app(ModuleRegistry::class)->snapshot();
        $record = $snapshot->modules[$module] ?? null;
        if ($snapshot->fenced !== [] || $record === null || $record->installedVersion !== $record->sourceVersion
            || ! app(ModuleAvailability::class)->statuses($module)[$module]->available) {
            throw new RuntimeException('Initialize/update the enabled module before development fixtures.');
        }
        $ownerRoot = base_path($identity->owner.'/modules/'.$identity->code.'/backend');
        $this->owned($class, $ownerRoot);
        $contributor = app($class);
        if (! $contributor instanceof DevelopmentContributor) {
            throw new RuntimeException('Module contributor must implement DevelopmentContributor.');
        }

        return $contributor;
    }

    /** @return list<class-string<ManagedSeeder>> */
    public function registered(): array
    {
        self::assertAllowed();
        $modules = config('seeding.contributors', []);
        if (! is_array($modules)) {
            throw new RuntimeException('Module contributors must be an explicit module/class map.');
        }
        ksort($modules, SORT_STRING);
        $classes = [];
        foreach (array_keys($modules) as $module) {
            $identity = new ModuleIdentity($module);
            foreach ($this->contributor($module)->seeders() as $class) {
                $this->owned($class, base_path($identity->owner.'/modules/'.$identity->code.'/backend'));
                if (! is_subclass_of($class, ManagedSeeder::class) || $class::category() !== SeedCategory::Development) {
                    throw new RuntimeException('Module development contributors may register only development ManagedSeeders.');
                }
                $classes[] = $class;
            }
        }

        return array_values(array_unique($classes));
    }

    /** Validate both contributor and seeder dependencies before any mutation.
     * @param  list<class-string<ManagedSeeder>>  $applicationClasses
     */
    public function graph(array $applicationClasses): SeedGraph
    {
        $classes = $this->registered();
        $dependencies = [];
        $state = [];
        $allSeeders = [];
        $visit = function (string $module) use (&$visit, &$state, &$dependencies, &$allSeeders): array {
            if (($state[$module] ?? 0) === 1) {
                throw new RuntimeException('Module development contributor dependency cycle.');
            }
            if (($state[$module] ?? 0) === 2) {
                return $allSeeders[$module];
            }
            $state[$module] = 1;
            $contributor = $this->contributor($module);
            $allSeeders[$module] = $contributor->seeders();
            foreach ($contributor->dependsOn() as $dependency) {
                // Retain transitive ordering even when an intermediate contributor has no seeders.
                $required = $visit($dependency);
                foreach ($contributor->seeders() as $class) {
                    $dependencies[$class] = [...($dependencies[$class] ?? []), ...$required];
                }
                $allSeeders[$module] = array_values(array_unique([...$allSeeders[$module], ...$required]));
            }
            $state[$module] = 2;

            return $allSeeders[$module];
        };
        foreach (array_keys(config('seeding.contributors', [])) as $module) {
            $visit($module);
        }

        return new SeedGraph([...$applicationClasses, ...$classes], $dependencies);
    }

    /** @template T
     * @param  Closure(Connection): T  $work
     * @return T
     */
    public function run(string $module, Closure $work): mixed
    {
        self::assertAllowed();

        return app('db')->connection()->transaction(function (Connection $connection) use ($module, $work): mixed {
            $connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first()
                ?? throw new RuntimeException('Module registry unavailable.');
            app(AuthorityLock::class)->acquire($connection);
            $this->contributor($module);

            return $work($connection);
        });
    }

    private function owned(string $class, string $root): void
    {
        $file = (new ReflectionClass($class))->getFileName();
        if (! is_string($file)) {
            throw new RuntimeException('Contributor source must belong to its module.');
        }
        ModuleDiscovery::ownedPath($root, $file);
    }
}
