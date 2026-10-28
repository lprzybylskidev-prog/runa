<?php

namespace Runa\ModuleFramework\Events;

use Illuminate\Database\Connection;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\IntegrationDefinition;
use Runa\Contracts\Modules\IntegrationProvider;
use Runa\ModuleFramework\Api\BoundaryValues;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Support\Modules\ModuleRecord;
use Runa\Support\VersionCompatibility;
use RuntimeException;

/** Trusted management writes; no HTTP management or implicit module activation. */
final readonly class ModuleIntegrations
{
    public function __construct(private Connection $connection, private ModuleRegistry $registry, private ModuleDeclarations $declarations, private OperationGuard $guard) {}

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
                if (isset($result[$definition->key]) || ! isset($record->optional[$definition->target]) || ! $provider->validConfiguration($definition->key, $definition->defaults)) {
                    throw new RuntimeException('Invalid integration declaration or defaults.');
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
        $stored = $this->values($module);
        $result = [];
        foreach ($this->definitions($record) as $key => [$definition, $provider]) {
            $value = $stored[$key] ?? ['enabled' => $definition->defaultEnabled, 'config' => $definition->defaults];
            if (! $provider->validConfiguration($key, $value['config'])) {
                throw new RuntimeException('Stored integration configuration requires repair.');
            }
            $result[$key] = ['definition' => $definition, ...$value];
        }

        return $result;
    }

    /** @param array<string, mixed> $config */
    public function configure(string $module, string $key, bool $enabled, array $config, int $expectedGeneration): int
    {
        BoundaryValues::check($config, "\0");
        if (strlen(json_encode($config, JSON_THROW_ON_ERROR)) > 8192) {
            throw new RuntimeException('Integration configuration too large.');
        }

        return $this->connection->transaction(function () use ($module, $key, $enabled, $config, $expectedGeneration): int {
            $this->connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first();
            $snapshot = $this->registry->snapshot();
            if ($snapshot->generation !== $expectedGeneration || $this->connection->table('shared.module_transitions')->whereIn('status', ModuleRegistry::ACTIVE_TRANSITIONS)->exists()) {
                throw new RuntimeException('Integration revision changed or transition unresolved.');
            }
            $record = $snapshot->modules[$module] ?? throw new RuntimeException('Unknown module.');
            [, $provider] = $this->definitions($record)[$key] ?? throw new RuntimeException('Unknown integration.');
            if (! $provider->validConfiguration($key, $config)) {
                throw new RuntimeException('Invalid integration configuration.');
            }
            $values = $this->values($module);
            $values[$key] = ['enabled' => $enabled, 'config' => $config];
            $this->connection->table('shared.modules')->where('module_id', $module)->update(['integration_values' => json_encode((object) $values, JSON_THROW_ON_ERROR), 'updated_at' => now()]);
            $this->connection->table('shared.module_registry_state')->where('id', 1)->increment('generation');

            return $expectedGeneration + 1;
        });
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

    /** @return array<string, array{enabled: bool, config: array<string, mixed>}> */
    private function values(string $module): array
    {
        $json = $this->connection->table('shared.modules')->where('module_id', $module)->value('integration_values');
        if (! is_string($json)) {
            throw new RuntimeException('Integration migration required.');
        }
        $decoded = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new RuntimeException('Invalid stored integrations.');
        }
        $values = [];
        foreach ($decoded as $key => $entry) {
            if (! is_string($key) || ! is_array($entry) || count($entry) !== 2 || ! is_bool($entry['enabled'] ?? null) || ! is_array($entry['config'] ?? null)) {
                throw new RuntimeException('Invalid stored integration value.');
            }
            $config = [];
            foreach ($entry['config'] as $configKey => $value) {
                if (! is_string($configKey)) {
                    throw new RuntimeException('Integration configuration requires named fields.');
                }
                $config[$configKey] = $value;
            }
            $values[$key] = ['enabled' => $entry['enabled'], 'config' => $config];
        }

        return $values;
    }
}
