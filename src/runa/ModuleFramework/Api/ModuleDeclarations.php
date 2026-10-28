<?php

namespace Runa\ModuleFramework\Api;

use Illuminate\Contracts\Container\Container;
use ReflectionClass;
use Runa\ModuleFramework\ModuleAutoloader;
use Runa\ModuleFramework\ModuleDiscovery;
use Runa\ModuleFramework\ModuleManifest;
use Runa\Support\Modules\ModuleRecord;
use RuntimeException;

/** Resolves trusted source descriptors, never class/path names from operational DB settings. */
final readonly class ModuleDeclarations
{
    public function __construct(private string $root, private Container $container, private ModuleAutoloader $loader, private bool $development) {}

    public function manifest(ModuleRecord $record): ModuleManifest
    {
        if (! $record->registered || ! $record->sourcePresent) {
            throw new RuntimeException('Module declaration source unavailable.');
        }
        [$owner, $code] = explode('.', $record->id, 2);
        $path = ModuleDiscovery::ownedPath($this->root, $this->root.'/'.$record->sourcePath);
        $manifest = ModuleManifest::load($path.'/module.json', $owner, $code, $this->development);
        if (hash_file('sha256', $path.'/module.json') !== $record->manifestHash || $manifest->kind !== $record->kind) {
            throw new RuntimeException('Module declarations require synchronization.');
        }

        return $manifest;
    }

    /** @return list<string> */
    public function classes(ModuleRecord $record, string $category): array
    {
        $manifest = $this->manifest($record);
        /** @var array<string, list<string>> $declarations */
        $declarations = (array) $manifest->definition['declarations'];

        return $declarations[$category];
    }

    /** @return ReflectionClass<object> */
    public function inspect(ModuleRecord $record, string $class): ReflectionClass
    {
        $manifest = $this->manifest($record);
        if (! str_starts_with($class, $manifest->prefix) || preg_match('/\A[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)+\z/D', $class) !== 1) {
            throw new RuntimeException('Public declaration must be module-owned.');
        }
        if (! class_exists($class, false) && ! interface_exists($class, false)) {
            $this->loader->load($class);
        }
        if (! class_exists($class, false) && ! interface_exists($class, false)) {
            throw new RuntimeException('Public declaration class missing.');
        }
        $reflection = new ReflectionClass($class);
        $file = $reflection->getFileName();
        $root = ModuleDiscovery::ownedPath($this->root, $this->root.'/'.$record->sourcePath);
        if ($file === false || ! str_starts_with(ModuleDiscovery::ownedPath($root, $file), $root.'/backend/')) {
            throw new RuntimeException('Public declaration escaped module source.');
        }

        return $reflection;
    }

    public function make(ModuleRecord $record, string $class, ?string $contract = null): object
    {
        $reflection = $this->inspect($record, $class);
        if (! $reflection->isInstantiable() || ($contract !== null && ! $reflection->implementsInterface($contract))) {
            throw new RuntimeException('Invalid module declaration interface.');
        }
        $instance = $this->container->make($class);
        if (! is_object($instance) || $instance::class !== $class) {
            throw new RuntimeException('Public declaration binding replaced owned class.');
        }

        return $instance;
    }
}
