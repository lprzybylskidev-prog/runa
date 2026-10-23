<?php

namespace Runa\Contracts\Modules;

final readonly class ModuleManagement
{
    public int $schemaVersion;

    /** @param array<string, mixed> $metadata
     * @param  array<string, mixed>|null  $settings  Redacted projection only.
     * @param  array<string, mixed>  $integrations
     * @param  list<WorkStatus>  $activeWork
     * @param  array<string, ManagementPermission>  $permissions
     * @param  array<string, ManagementAction>  $actions
     * @param  array<string, ManagementComponent>  $components
     * @param  list<string>  $diagnostics
     */
    public function __construct(public string $moduleId, public int $generation, public ModuleStatus $status, public array $metadata, public ?array $settings, public array $integrations, public array $activeWork, public bool $workTruncated, public array $permissions, public array $actions, public array $components, public array $diagnostics)
    {
        $this->schemaVersion = 1;
    }
}
