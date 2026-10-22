<?php

namespace Runa\Support\Modules\Updates;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Support\Modules\Data\ModuleDataPaths;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleRecord;
use Runa\Support\VersionCompatibility;
use RuntimeException;

/** Read-only inventories and pure descriptors, never includes a migration or constructs a handler. */
final readonly class ModuleUpdatePlanner
{
    public function __construct(private string $root, private string $platform, private bool $development = false) {}

    /** @param array<string, array{installed: ?string, version: string, registered: bool, required: array<string, string>}> $state
     * @return list<PlannedModule>
     */
    public function plan(string $owner, array $state): array
    {
        $sources = (new ModuleDiscovery($this->root, $this->development))->discover($owner);
        $versions = array_map(fn (array $row): string => $row['version'], array_filter($state, fn (array $row): bool => $row['registered']));
        $byId = [];
        foreach ($sources as $source) {
            $id = $source->manifest->id;
            if (isset($state[$id]) && ! $state[$id]['registered']) {
                throw new RuntimeException('Explicitly re-register retained module source before updating.');
            }
            $versions[$id] = $source->manifest->version;
            $byId[$id] = $source;
        }
        foreach ($state as $id => $row) {
            if (str_starts_with($id, $owner.'.') && $row['registered'] && ! isset($byId[$id])) {
                throw new RuntimeException('Registered module source is missing from the update.');
            }
        }
        $compatibility = new VersionCompatibility;
        $requirements = [];
        foreach ($state as $id => $row) {
            if ($row['registered']) {
                $requirements[$id] = $row['required'];
            }
        }
        foreach ($sources as $source) {
            $manifest = $source->manifest;
            $platform = $manifest->definition['platform'];
            $api = $manifest->definition['framework_api'];
            if (! is_string($platform) || ! is_string($api) || ! $compatibility->supports($platform, $this->platform) || ! $compatibility->supports($api, '1.0.0')) {
                throw new RuntimeException('Module platform or framework contract is incompatible.');
            }
            /** @var array{required: array<string, string>} $dependencies */
            $dependencies = (array) $manifest->definition['dependencies'];
            $requirements[$manifest->id] = (array) $dependencies['required'];
        }
        // Include reverse app requirements, but never load/execute app upgrade declarations in a Runa update.
        foreach ($requirements as $dependencies) {
            foreach ($dependencies as $id => $range) {
                if (! isset($versions[$id]) || ! $compatibility->supports($range, $versions[$id])) {
                    throw new RuntimeException('Required module dependency is absent or incompatible.');
                }
            }
        }
        $ordered = [];
        $visiting = [];
        $visit = function (string $id) use (&$visit, &$ordered, &$visiting, $requirements): void {
            if (isset($ordered[$id])) {
                return;
            }
            if (isset($visiting[$id])) {
                throw new RuntimeException('Required module update graph contains a cycle.');
            }
            $visiting[$id] = true;
            foreach (array_keys($requirements[$id] ?? []) as $dependency) {
                $visit($dependency);
            }
            unset($visiting[$id]);
            $ordered[$id] = true;
        };
        foreach (array_keys($byId) as $id) {
            $visit($id);
        }
        $result = [];
        foreach (array_keys($ordered) as $id) {
            if (! isset($byId[$id])) {
                continue;
            }
            $source = $byId[$id];
            $manifest = $source->manifest;
            $installed = $state[$id]['installed'] ?? null;
            if ($installed !== null && version_compare($installed, $manifest->version, '>')) {
                throw new RuntimeException('Module data downgrades are unsupported.');
            }
            $definitions = (new UpgradeDefinitions($this->root))->steps($source, $installed);
            $steps = $this->chain($definitions, $installed, $manifest->version);
            $record = new ModuleRecord('', $id, $manifest->kind, true, true, false, $manifest->version, $installed, $source->path, $source->manifestHash, [], [], '*', '*');
            $migrations = [];
            foreach ((new ModuleDataPaths($this->root, $this->development))->migrations($record) as $file) {
                $migrations[basename($file, '.php')] = hash_file('sha256', $file) ?: throw new RuntimeException('Cannot hash module migration.');
            }
            $hashes = [];
            $directory = $this->root.'/'.$source->path;
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file instanceof \SplFileInfo && $file->isFile()) {
                    $hashes[substr($file->getPathname(), strlen($directory))] = hash_file('sha256', $file->getPathname());
                }
            }
            ksort($hashes);
            $result[] = new PlannedModule($source, $installed, $migrations, $steps, hash('sha256', json_encode($hashes, JSON_THROW_ON_ERROR)));
        }

        return $result;
    }

    /** A version edge is one explicit upgrade; use its handler to group atomic changes.
     * @param  array<string, UpgradeDefinition>  $definitions
     * @return list<UpgradeDefinition>
     */
    private function chain(array $definitions, ?string $installed, string $target): array
    {
        if ($installed === $target) {
            return [];
        }
        // First installation without data upgrades initializes directly from the current schema.
        if ($installed === null && $definitions === []) {
            return [];
        }
        $path = [];
        $current = $installed;
        while ($current !== $target) {
            $next = array_values(array_filter($definitions, fn (UpgradeDefinition $step): bool => $step->from === $current && version_compare($step->to, $target, '<=')));
            if (count($next) !== 1 || count($path) >= 256) {
                throw new RuntimeException('Missing or ambiguous module upgrade path.');
            }
            $step = $next[0];
            $path[$step->key] = $step;
            $current = $step->to;
        }
        foreach ($path as $key => $step) {
            foreach ($step->requires as $required) {
                if (! isset($definitions[$required]) || (isset($path[$required]) && array_search($required, array_keys($path), true) >= array_search($key, array_keys($path), true)) || (! isset($path[$required]) && ($installed === null || version_compare($definitions[$required]->to, $installed, '>')))) {
                    throw new RuntimeException('Module upgrade prerequisite is missing or unordered.');
                }
            }
        }

        return array_values($path);
    }
}
