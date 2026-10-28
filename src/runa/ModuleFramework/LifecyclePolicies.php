<?php

namespace Runa\ModuleFramework;

use Illuminate\Contracts\Container\Container;
use ReflectionClass;
use Runa\Contracts\Modules\LifecyclePolicy;
use Runa\Support\Modules\ModuleRecord;
use RuntimeException;

/** Resolve only the source-owned lifecycle declaration; never a persisted executable class path. */
final readonly class LifecyclePolicies
{
    public function __construct(private string $root, private Container $container, private ModuleAutoloader $autoloader, private bool $development) {}

    public function resolve(ModuleRecord $record): LifecyclePolicy
    {
        if (! $record->registered || ! $record->sourcePresent) {
            throw new RuntimeException('Lifecycle source is unavailable.');
        }
        $root = ModuleDiscovery::ownedPath($this->root, $this->root.'/'.$record->sourcePath);
        [$owner, $code] = explode('.', $record->id, 2);
        $manifest = ModuleManifest::load($root.'/module.json', $owner, $code, $this->development);
        if (hash_file('sha256', $root.'/module.json') !== $record->manifestHash || $manifest->kind !== $record->kind) {
            throw new RuntimeException('Lifecycle source requires synchronization.');
        }
        /** @var array<string, list<string>> $declarations */
        $declarations = (array) $manifest->definition['declarations'];
        $class = $declarations['lifecycle'][0];
        if (! class_exists($class, false)) {
            $this->autoloader->load($class);
        }
        if (! class_exists($class, false)) {
            throw new RuntimeException('Lifecycle class is missing.');
        }
        $reflection = new ReflectionClass($class);
        $file = $reflection->getFileName();
        if (! $reflection->implementsInterface(LifecyclePolicy::class) || $file === false || ! str_starts_with(ModuleDiscovery::ownedPath($root, $file), $root.'/backend/')) {
            throw new RuntimeException('Invalid lifecycle descriptor.');
        }
        $policy = $this->container->make($class);
        if (! $policy instanceof LifecyclePolicy || $policy->schemaVersion() !== 1) {
            throw new RuntimeException('Unsupported lifecycle descriptor.');
        }

        return $policy;
    }
}
