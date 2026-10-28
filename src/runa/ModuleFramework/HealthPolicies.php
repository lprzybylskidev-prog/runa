<?php

namespace Runa\ModuleFramework;

use Illuminate\Contracts\Container\Container;
use ReflectionClass;
use Runa\Contracts\Modules\HealthPolicy;
use Runa\Support\Modules\ModuleRecord;
use RuntimeException;

final readonly class HealthPolicies
{
    public function __construct(private string $root, private Container $container, private ModuleAutoloader $autoloader, private bool $development) {}

    public function resolve(ModuleRecord $record): ?HealthPolicy
    {
        if (! $record->registered || ! $record->sourcePresent) {
            throw new RuntimeException('Health source is unavailable.');
        }
        $root = ModuleDiscovery::ownedPath($this->root, $this->root.'/'.$record->sourcePath);
        [$owner, $code] = explode('.', $record->id, 2);
        $manifest = ModuleManifest::load($root.'/module.json', $owner, $code, $this->development);
        if (hash_file('sha256', $root.'/module.json') !== $record->manifestHash || $manifest->kind !== $record->kind) {
            throw new RuntimeException('Health source requires synchronization.');
        }
        /** @var array<string, list<class-string>> $declarations */
        $declarations = (array) $manifest->definition['declarations'];
        $classes = $declarations['health'];
        if (count($classes) > 1) {
            throw new RuntimeException('Declare one module-owned health policy.');
        }
        if ($classes === []) {
            return null;
        }
        $class = $classes[0];
        if (! class_exists($class, false)) {
            $this->autoloader->load($class);
        }
        if (! class_exists($class, false)) {
            throw new RuntimeException('Health class is missing.');
        }
        $reflection = new ReflectionClass($class);
        $file = $reflection->getFileName();
        if (! $reflection->implementsInterface(HealthPolicy::class) || $file === false || ! str_starts_with(ModuleDiscovery::ownedPath($root, $file), $root.'/backend/')) {
            throw new RuntimeException('Invalid health descriptor.');
        }
        $policy = $this->container->make($class);
        if (! $policy instanceof HealthPolicy || $policy->schemaVersion() !== 1) {
            throw new RuntimeException('Unsupported health descriptor.');
        }

        return $policy;
    }
}
