<?php

namespace Runa\ModuleFramework\Management;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Runa\ModuleFramework\ModuleDiscovery;
use Throwable;

/** Internal, source-bound evidence shared with the frontend build registry. */
final readonly class ManagementBuilds
{
    public function __construct(private string $root, private bool $compiledOnly = false) {}

    /** @return array{build_id:string,sources:array<string,string>} */
    public function evidence(string $directory, ?string $manifestHash): array
    {
        $sources = [];
        $directory = ModuleDiscovery::ownedPath($this->root, $directory);
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
            $path = ModuleDiscovery::ownedPath($this->root, $file->getPathname());
            if (! $file->isFile()) {
                throw new InvalidArgumentException('Invalid frontend build source.');
            }
            $hash = hash_file('sha256', $path);
            if ($hash === false || count($sources) >= 16384) {
                throw new InvalidArgumentException('Invalid frontend source evidence.');
            }
            $sources[substr($path, strlen($directory) + 1)] = $hash;
        }
        ksort($sources, SORT_STRING);

        return ['build_id' => $this->identity($sources, $manifestHash), 'sources' => $sources];
    }

    /** @param array<string,string> $sources */
    private function identity(array $sources, ?string $manifestHash): string
    {
        $identity = ($manifestHash ?? 'app')."\n";
        foreach ($sources as $path => $hash) {
            $identity .= $path.':'.$hash."\n";
        }

        return hash('sha256', $identity);
    }

    /** @return array{key:string,owner:string,manifest_hash:?string,build_id:?string} */
    public function component(string $owner, string $key, string $directory, ?string $manifestHash): array
    {
        $view = ['key' => $key, 'owner' => $owner, 'manifest_hash' => $manifestHash, 'build_id' => null];
        try {
            $file = ModuleDiscovery::ownedPath($this->root, $this->root.'/public/build/runa-components.json');
            if (! is_file($file) || filesize($file) > 4194304) {
                return $view;
            }
            $manifest = json_decode(file_get_contents($file), true, 32, JSON_THROW_ON_ERROR);
            $entry = $manifest['owners'][$owner] ?? null;
            if (($manifest['schema_version'] ?? null) !== 1 || ! is_array($entry)
                || ($entry['manifest_hash'] ?? null) !== $manifestHash || ! in_array($key, $entry['components'] ?? [], true)) {
                return $view;
            }
            $sources = $entry['sources'] ?? null;
            if (! is_array($sources) || $sources === [] || count($sources) > 16384) {
                return $view;
            }
            foreach ($sources as $path => $hash) {
                if (! is_string($path) || str_contains($path, '..') || str_starts_with($path, '/') || str_contains($path, '\\')
                    || ! is_string($hash) || preg_match('/\A[a-f0-9]{64}\z/D', $hash) !== 1) {
                    return $view;
                }
            }
            $sourceKey = ($owner === 'app' ? 'js/components/admin/' : 'components/').substr($key, strlen($owner) + 1).'.vue';
            if (! isset($sources[$sourceKey])) {
                return $view;
            }
            ksort($sources, SORT_STRING);
            $evidence = $this->compiledOnly
                ? ['build_id' => $this->identity($sources, $manifestHash), 'sources' => $sources]
                : $this->evidence($directory, $manifestHash);
            if (($entry['build_id'] ?? null) === $evidence['build_id'] && $sources === $evidence['sources']) {
                $view['build_id'] = $evidence['build_id'];
            }
        } catch (Throwable) {
            // Missing/stale/malformed evidence never selects the supplied default.
        }

        return $view;
    }
}
