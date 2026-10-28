<?php

namespace Runa\ModuleFramework\Work;

use Runa\Contracts\Modules\JobDefinition;
use Runa\Contracts\Modules\JobProvider;
use Runa\Contracts\Modules\WorkHandler;
use Runa\Contracts\Modules\WorkPolicy;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Support\Modules\ModuleRecord;
use RuntimeException;

final readonly class WorkDefinitions
{
    public function __construct(private ModuleRegistry $registry, private ModuleDeclarations $declarations) {}

    /** @return array{ModuleRecord, JobDefinition} */
    public function resolve(string $module, string $operation): array
    {
        $record = $this->registry->snapshot()->modules[$module] ?? throw new RuntimeException('Module work source missing.');
        $definitions = [];
        foreach ($this->declarations->classes($record, 'jobs') as $class) {
            $provider = $this->declarations->make($record, $class, JobProvider::class);
            if (! $provider instanceof JobProvider) {
                throw new RuntimeException('Invalid job provider.');
            }
            foreach ($provider->jobs() as $definition) {
                if (isset($definitions[$definition->key])) {
                    throw new RuntimeException('Duplicate job declaration.');
                }
                foreach ([[$definition->handler, WorkHandler::class], [$definition->policy, WorkPolicy::class]] as [$target, $contract]) {
                    $type = $this->declarations->inspect($record, $target);
                    if (! $type->isInstantiable() || ! $type->implementsInterface($contract)) {
                        throw new RuntimeException('Invalid job handler or policy.');
                    }
                }
                $definitions[$definition->key] = $definition;
            }
        }

        return [$record, $definitions[$operation] ?? throw new RuntimeException('Unknown module job.')];
    }

    public function policy(ModuleRecord $record, JobDefinition $definition): WorkPolicy
    {
        $policy = $this->declarations->make($record, $definition->policy, WorkPolicy::class);
        if (! $policy instanceof WorkPolicy || $policy->schemaVersion() !== 1) {
            throw new RuntimeException('Unsupported work policy.');
        }

        return $policy;
    }

    public function handler(ModuleRecord $record, JobDefinition $definition): WorkHandler
    {
        $handler = $this->declarations->make($record, $definition->handler, WorkHandler::class);
        if (! $handler instanceof WorkHandler) {
            throw new RuntimeException('Invalid work handler.');
        }

        return $handler;
    }
}
