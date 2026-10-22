<?php

namespace Runa\Support\Modules\Api;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\OperationContext;
use Runa\Support\MaintenanceState;
use Runa\Support\Modules\ModuleAvailability;
use Runa\Support\Modules\ModuleRegistry;
use Throwable;

final readonly class OperationGuard
{
    public function __construct(private ModuleRegistry $registry, private ModuleAvailability $availability, private MaintenanceState $maintenance, private OperationAuthorizer $authorizer, private Application $app, private OperationScope $scope) {}

    public function enter(string $module, ApiOperation $operation): OperationContext
    {
        try {
            if ($this->maintenance->active()) {
                throw new OperationRejected('platform_unavailable', $module, $operation->key);
            }
            $status = $this->availability->statuses($module, $operation->key)[$module];
            if (! $status->available) {
                throw new OperationRejected('module_unavailable', $module, $operation->key, reasons: $status->reasons);
            }
            $id = (string) Str::uuid7();
            $parent = $this->scope->current();
            $request = $this->app->bound('request') ? $this->app->make('request') : null;
            $candidate = $request instanceof Request ? $request->attributes->get('request_id') : null;
            $correlation = is_string($candidate) && preg_match('/\A[a-zA-Z0-9_-]{1,64}\z/D', $candidate) === 1 ? $candidate : $id;
            $context = new OperationContext($module, $operation->key, $id, $parent !== null ? $parent->correlationId : $correlation, $status->generation, $parent?->moduleId);
            if ($operation->permission !== null && ! $this->authorizer->allows($context, $operation->permission)) {
                throw new OperationRejected('module_forbidden', $module, $operation->key, 403);
            }
            $this->assertCurrent($context);

            return $context;
        } catch (OperationRejected $error) {
            throw $error;
        } catch (Throwable) {
            throw new OperationRejected('module_unavailable', $module, $operation->key);
        }
    }

    public function assertCurrent(OperationContext $context): void
    {
        if ($this->registry->snapshot()->generation !== $context->generation || $this->app->maintenanceMode()->active()
            || file_exists($this->app->storagePath('framework/maintenance/enabled'))) {
            throw new OperationRejected('availability_changed', $context->moduleId, $context->operation);
        }
    }
}
