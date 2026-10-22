<?php

namespace Runa\Support\Modules\Updates;

use Illuminate\Container\Container;
use ReflectionClass;
use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Contracts\Modules\UpgradeHandler;
use Runa\Contracts\Modules\UpgradeProvider;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleSource;
use RuntimeException;

/** Also used by the isolated target-release planner before registry synchronization. */
final readonly class UpgradeDefinitions
{
    public function __construct(private string $root) {}

    /** @param class-string $contract
     * @return ReflectionClass<object>
     */
    private function inspect(ModuleSource $source, string $class, string $contract): ReflectionClass
    {
        $prefix = $source->manifest->prefix;
        if (! str_starts_with($class, $prefix) || preg_match('/\A[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)+\z/D', $class) !== 1) {
            throw new RuntimeException('Upgrade declarations must be module-owned.');
        }
        $directory = ModuleDiscovery::ownedPath($this->root, $this->root.'/'.$source->path);
        $path = ModuleDiscovery::ownedPath($directory, $directory.'/backend/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php');
        if (! class_exists($class, false)) {
            require_once $path;
        }
        if (! class_exists($class, false)) {
            throw new RuntimeException('Upgrade declaration class is missing.');
        }
        $reflection = new ReflectionClass($class);
        if ($reflection->getFileName() !== $path || ! $reflection->isInstantiable() || ! $reflection->implementsInterface($contract)) {
            throw new RuntimeException('Invalid source-owned upgrade contract.');
        }

        return $reflection;
    }

    /** @template T of object
     * @param  class-string<T>  $contract
     * @return T
     */
    public function instance(ModuleSource $source, string $class, string $contract): object
    {
        $this->inspect($source, $class, $contract);
        $instance = (new Container)->make($class);
        if (! $instance instanceof $contract || $instance::class !== $class) {
            throw new RuntimeException('Upgrade declaration binding changed.');
        }

        return $instance;
    }

    /** @return array<string, UpgradeDefinition> */
    public function steps(ModuleSource $source, ?string $installed): array
    {
        /** @var array<string, list<string>> $categories */
        $categories = (array) $source->manifest->definition['declarations'];
        $steps = [];
        foreach ($categories['upgrades'] as $class) {
            $provider = $this->instance($source, $class, UpgradeProvider::class);
            $blocker = $provider->preflight($installed, $source->manifest->version);
            if ($blocker !== null) {
                throw new RuntimeException('Module upgrade preflight refused.');
            }
            foreach ($provider->upgrades() as $step) {
                if (isset($steps[$step->key]) || count($steps) >= 256) {
                    throw new RuntimeException('Invalid or duplicate module upgrade step.');
                }
                // Load/reflection-check trusted class metadata; never construct or run the handler.
                $this->inspect($source, $step->handler, UpgradeHandler::class);
                $steps[$step->key] = $step;
            }
        }

        return $steps;
    }
}
