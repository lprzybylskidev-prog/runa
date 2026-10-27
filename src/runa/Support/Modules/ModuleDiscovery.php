<?php

namespace Runa\Support\Modules;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final readonly class ModuleDiscovery
{
    public function __construct(private string $root, private bool $development = false) {}

    /** @return list<ModuleSource> */
    public function discover(string $owner): array
    {
        if (! in_array($owner, ['runa', 'app'], true)) {
            throw new InvalidArgumentException('Module discovery requires an explicit runa or app owner.');
        }
        if (realpath($this->root) !== $this->root) {
            throw new InvalidArgumentException('Module application root must be canonical.');
        }
        $ownerRoot = $this->root.'/'.$owner;
        if (file_exists($ownerRoot) || is_link($ownerRoot)) {
            self::ownedPath($this->root, $ownerRoot);
        }
        $directory = $this->root.'/'.$owner.'/modules';
        if (! file_exists($directory) && ! is_link($directory)) {
            return [];
        }
        self::ownedPath($this->root, $directory);
        $sources = [];
        $prefixes = [];
        foreach (scandir($directory) ?: [] as $code) {
            if ($code === '.' || $code === '..') {
                continue;
            }
            $path = $directory.'/'.$code;
            self::ownedPath($directory, $path);
            if (in_array($code, ['.gitignore', '.gitkeep'], true) && is_file($path)) {
                continue;
            }
            if (! is_dir($path)) {
                throw new InvalidArgumentException('Module source roots must be directories.');
            }
            if (in_array($code, ['module_probe', 'reference'], true) && ! $this->development) {
                throw new InvalidArgumentException('Development example modules must not be present in production.');
            }
            $manifest = ModuleManifest::load($path.'/module.json', $owner, $code, $this->development);
            // Reject nested symlinks before any class or declaration can be loaded.
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $entry) {
                if (! $entry instanceof \SplFileInfo) {
                    throw new InvalidArgumentException('Invalid module source entry.');
                }
                self::ownedPath($path, $entry->getPathname());
            }
            $prefix = strtolower($manifest->prefix);
            if (isset($prefixes[$prefix]) || isset($sources[$manifest->id])) {
                throw new InvalidArgumentException('Duplicate module identity or PHP namespace.');
            }
            $segment = explode('\\', $manifest->prefix)[2];
            $ordinary = $this->root.'/'.$owner.'/Modules';
            foreach (is_dir($ordinary) ? (scandir($ordinary) ?: []) : [] as $name) {
                if (strcasecmp($name, $segment) === 0 || strcasecmp($name, $segment.'.php') === 0) {
                    throw new InvalidArgumentException('Module namespace collides with ordinary source.');
                }
            }
            foreach ($manifest->classes as $class) {
                if (! is_file(self::ownedPath($path, $path.'/backend/'.str_replace('\\', '/', substr($class, strlen($manifest->prefix))).'.php'))) {
                    throw new InvalidArgumentException('Module declaration source must be a regular PHP file.');
                }
            }
            foreach ($manifest->paths as $relative) {
                // Central test paths are development metadata, like central documentation.
                // Production discovery never requires shipped tests or loads test code.
                if (preg_match('~\Atests/(backend|frontend|tools)/~', $relative) === 1) {
                    continue;
                }
                $target = self::ownedPath($path, $path.'/'.$relative);
                if (! is_dir($target)) {
                    throw new InvalidArgumentException('Module test paths must be directories.');
                }
            }
            $prefixes[$prefix] = true;
            $hash = hash_file('sha256', $path.'/module.json');
            if ($hash === false) {
                throw new InvalidArgumentException('Module manifest cannot be hashed.');
            }
            $sources[$manifest->id] = new ModuleSource($manifest, $owner.'/modules/'.$code, $hash);
        }
        ksort($sources, SORT_STRING);

        return array_values($sources);
    }

    public static function ownedPath(string $root, string $path): string
    {
        $resolvedRoot = realpath($root);
        $resolved = realpath($path);
        if ($resolvedRoot !== $root || $resolved !== $path || ! str_starts_with($resolved, $root.'/')) {
            throw new InvalidArgumentException('Module path is missing, noncanonical or traverses a symlink.');
        }

        return $resolved;
    }
}
