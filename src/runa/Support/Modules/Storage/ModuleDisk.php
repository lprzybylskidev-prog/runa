<?php

namespace Runa\Support\Modules\Storage;

use FilesystemIterator;
use InvalidArgumentException;
use League\Flysystem\FilesystemOperator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Runa\ModuleFramework\Storage\LocalStorageBoundary;
use Runa\Support\Modules\ModuleIdentity;
use RuntimeException;

/** Narrow disk facade: no raw adapter, absolute path, URL, driver or arbitrary options. */
final readonly class ModuleDisk
{
    public function __construct(private FilesystemOperator $disk, private ModuleIdentity $module, private ?string $localRoot = null) {}

    public function put(string $path, string $contents): void
    {
        $this->disk->write($this->path($path), $contents);
    }

    /** @param resource $stream */
    public function writeStream(string $path, $stream): void
    {
        if (! is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException('Module writes require a stream resource.');
        }
        $this->disk->writeStream($this->path($path), $stream);
    }

    public function get(string $path): string
    {
        return $this->disk->read($this->path($path));
    }

    /** @return resource */
    public function readStream(string $path)
    {
        return $this->disk->readStream($this->path($path));
    }

    public function exists(string $path): bool
    {
        return $this->disk->fileExists($this->path($path));
    }

    public function delete(string $path): void
    {
        $this->disk->delete($this->path($path));
    }

    public function copy(string $from, string $to): void
    {
        $this->disk->copy($this->path($from), $this->path($to));
    }

    public function move(string $from, string $to): void
    {
        $this->disk->move($this->path($from), $this->path($to));
    }

    /** @return list<string> */
    public function files(string $directory = ''): array
    {
        $path = $this->path($directory, true);
        $this->inspectTree($path);
        $prefix = $this->module->storagePrefix();
        $files = [];
        foreach ($this->disk->listContents($path, true) as $entry) {
            if (! str_starts_with($entry->path(), $prefix)) {
                throw new RuntimeException('Storage driver returned a path outside the module.');
            }
            $relative = substr($entry->path(), strlen($prefix));
            $this->path($relative);
            if ($entry->isFile()) {
                $files[] = $relative;
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }

    public function deleteDirectory(string $directory): void
    {
        $path = $this->path($directory);
        $this->inspectTree($path);
        $this->disk->deleteDirectory($path);
    }

    private function path(string $relative, bool $allowEmpty = false): string
    {
        if (($relative === '' && ! $allowEmpty) || strlen($relative) > 1024
            || preg_match('/[\x00-\x1f\x7f\\\\:%?#]/', $relative) === 1 || str_starts_with($relative, '/')) {
            throw new InvalidArgumentException('Module storage requires a canonical relative path.');
        }
        foreach ($relative === '' ? [] : explode('/', $relative) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..' || trim($segment) !== $segment) {
                throw new InvalidArgumentException('Module storage path contains an invalid segment.');
            }
        }
        $path = $this->module->storagePrefix().$relative;
        if ($this->localRoot !== null) {
            // Check ancestors including the configured root; a symlinked root is also an escape.
            LocalStorageBoundary::check($this->localRoot.'/'.rtrim($path, '/'));
        }

        return rtrim($path, '/');
    }

    private function inspectTree(string $path): void
    {
        if ($this->localRoot === null || ! is_dir($this->localRoot.'/'.$path)) {
            return;
        }
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->localRoot.'/'.$path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $entry) {
            if ($entry instanceof \SplFileInfo && $entry->isLink()) {
                throw new RuntimeException('Module storage directory contains symbolic links.');
            }
        }
    }
}
