<?php

namespace Runa\ModuleFramework\Management;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use ReflectionClass;
use Runa\Contracts\Modules\ManagementPageProvider;
use Runa\ModuleFramework\ModuleDiscovery;

/** Source-owned bootstrap registrations; never a runtime/request extension registry. */
final class ApplicationManagement
{
    /** @var array<class-string, ManagementPageProvider> */
    private array $providers = [];

    private bool $frozen = false;

    public function __construct(private readonly Container $container, private readonly string $root) {}

    public function register(ManagementPageProvider $provider): void
    {
        if ($this->frozen || isset($this->providers[$provider::class]) || count($this->providers) >= 32 || $provider->permissions() !== []) {
            throw new InvalidArgumentException('Invalid or frozen application management registration.');
        }
        $this->inspect($provider::class);
        $this->providers[$provider::class] = $provider;
    }

    public function freeze(): void
    {
        $this->frozen = true;
    }

    /** @return list<ManagementPageProvider> */
    public function providers(): array
    {
        $this->freeze();
        ksort($this->providers);

        return array_values($this->providers);
    }

    /** Verify source identity before any handler constructor executes. */
    public function inspect(string $class): ReflectionClass
    {
        DeclarationRules::className($class);
        if (! str_starts_with($class, 'App\\') || str_starts_with($class, 'App\\Modules\\')) {
            throw new InvalidArgumentException('Application management class must be App-owned.');
        }
        $reflection = new ReflectionClass($class);
        $file = $reflection->getFileName();
        if ($file === false || ! str_starts_with(ModuleDiscovery::ownedPath($this->root, $file), $this->root.'/app/')) {
            throw new InvalidArgumentException('Application management source unavailable.');
        }

        return $reflection;
    }

    public function bound(string $class): bool
    {
        return $this->container->bound($class);
    }

    public function make(string $class): object
    {
        $this->inspect($class);
        if (! $this->container->bound($class)) {
            throw new InvalidArgumentException('Application management handlers require explicit container binding.');
        }
        $instance = $this->container->make($class);
        if (! is_object($instance) || $instance::class !== $class) {
            throw new InvalidArgumentException('Application management binding replaced owned class.');
        }

        return $instance;
    }
}
