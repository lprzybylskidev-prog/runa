<?php

namespace Runa\ModuleFramework\Events;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\IntegrationDefinition;
use Runa\Contracts\Modules\IntegrationProvider;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Support\Modules\ModuleRecord;
use Runa\Support\VersionCompatibility;
use RuntimeException;

/** Source-owned configuration; no runtime writes or implicit module activation. */
final readonly class ModuleIntegrations
{
    public function __construct(private ModuleRegistry $registry, private ModuleDeclarations $declarations, private OperationGuard $guard) {}

    /** @return array<string, array{IntegrationDefinition, IntegrationProvider}> */
    private function definitions(ModuleRecord $record): array
    {
        $result = [];
        foreach ($this->declarations->classes($record, 'integrations') as $class) {
            $provider = $this->declarations->make($record, $class, IntegrationProvider::class);
            if (! $provider instanceof IntegrationProvider) {
                throw new RuntimeException('Invalid integration provider.');
            }
            foreach ($provider->integrations() as $definition) {
                if (isset($result[$definition->key]) || ! isset($record->optional[$definition->target]) || ! $provider->validConfiguration($definition->key, $definition->config)) {
                    throw new RuntimeException('Invalid integration declaration or configuration.');
                }
                $result[$definition->key] = [$definition, $provider];
                if (count($result) > 128) {
                    throw new RuntimeException('Too many integrations.');
                }
            }
        }

        return $result;
    }

    /** @return array<string, array{definition: IntegrationDefinition, enabled: bool, config: array<string, mixed>}> */
    public function metadata(string $module): array
    {
        $record = $this->registry->snapshot()->modules[$module] ?? throw new RuntimeException('Unknown module.');
        $result = [];
        foreach ($this->definitions($record) as $key => [$definition]) {
            $result[$key] = ['definition' => $definition, 'enabled' => $definition->enabled, 'config' => $definition->config];
        }

        return $result;
    }

    /** Recheck code/config/version without executing health checks; callers still enforce operational admission. */
    public function requireConfigured(string $module, string $key): string
    {
        $entry = $this->metadata($module)[$key] ?? throw new RuntimeException('Unknown integration.');
        $definition = $entry['definition'];
        $snapshot = $this->registry->snapshot();
        $record = $snapshot->modules[$module];
        $target = $snapshot->modules[$definition->target] ?? throw new RuntimeException('Integration target missing.');
        if (! $entry['enabled'] || ! (new VersionCompatibility)->supports($record->optional[$target->id], $target->sourceVersion)) {
            throw new RuntimeException('Integration unavailable.');
        }

        return $target->id;
    }

    /** Every use checks both sides, including platform/Core maintenance. Returns target identity. */
    public function requireAvailable(string $module, string $key): string
    {
        $target = $this->requireConfigured($module, $key);
        $this->guard->enter($target, new ApiOperation('integration'));
        $context = $this->guard->enter($module, new ApiOperation('integration'));
        if ($this->requireConfigured($module, $key) !== $target) {
            throw new RuntimeException('Integration target changed.');
        }
        $this->guard->assertCurrent($context);

        return $target;
    }
}
