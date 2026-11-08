<?php

namespace Runa\Integration\Core;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\OperationContext;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Modules\Permissions\Contracts\Permissions;

/** Lazy framework binding remains fail-closed before Core data/source is ready. */
final readonly class PermissionAuthorizer implements OperationAuthorizer
{
    public function __construct(private ModuleRegistry $registry, private ModuleDeclarations $declarations, private ModuleAvailability $availability, private Application $app) {}

    public function allows(OperationContext $context, string $permission): bool
    {
        try {
            return DB::connection()->transaction(function () use ($context, $permission): bool {
                // Anchor before nested Auth policy reads. Never retain this decision.
                $this->registry->snapshot();

                return $this->decide($context, $permission);
            });
        } catch (\Throwable) {
            return false;
        }
    }

    private function decide(OperationContext $context, string $permission): bool
    {
        if ($this->app->maintenanceMode()->active()
            || file_exists($this->app->storagePath('framework/maintenance/enabled'))
            || ! $this->availability->statuses([$context->moduleId, 'runa.permissions'])[$context->moduleId]->available
            || $this->registry->snapshot()->generation !== $context->generation) {
            return false;
        }
        $record = $this->registry->snapshot()->modules['runa.permissions'] ?? null;
        if ($record === null || ! $this->availability->statuses('runa.permissions')['runa.permissions']->available) {
            return false;
        }
        $resolver = $this->declarations->make($record, 'Runa\\Modules\\Permissions\\Queries\\ResolvePermissions', Permissions::class);

        return $resolver instanceof Permissions && $resolver->allows($permission)
            && $this->registry->snapshot()->generation === $context->generation;
    }
}
