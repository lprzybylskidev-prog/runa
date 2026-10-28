<?php

namespace Runa\Runtime;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

final class RuntimeHeartbeat
{
    public function __construct(private readonly Filesystem $files) {}

    private function path(string $service): string
    {
        if (! in_array($service, ['queue', 'scheduler'], true)) {
            throw new RuntimeException('Unknown runtime service.');
        }

        return storage_path('framework/health/'.$service.'.json');
    }

    public function record(string $service): void
    {
        $path = $this->path($service);
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->replace($path, json_encode([
            'instance' => gethostname(), 'recorded_at' => time(),
        ], JSON_THROW_ON_ERROR));
    }

    public function isFresh(string $service, int $maxAge): bool
    {
        $path = $this->path($service);
        if (! $this->files->isFile($path)) {
            return false;
        }
        $data = json_decode($this->files->get($path), true);
        if (! is_array($data) || ($data['instance'] ?? null) !== gethostname() || ! is_int($data['recorded_at'] ?? null)) {
            return false;
        }
        $age = time() - $data['recorded_at'];

        return $age >= 0 && $age <= $maxAge;
    }
}
