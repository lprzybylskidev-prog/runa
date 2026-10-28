<?php

namespace Runa\ModuleFramework\Api;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\OperationContext;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Runtime\MaintenanceState;
use Runa\Support\Logging\LogContext;
use Runa\Support\Modules\Api\OperationRejected;
use Throwable;

final readonly class OperationGuard
{
    public function __construct(private ModuleRegistry $registry, private ModuleAvailability $availability, private MaintenanceState $maintenance, private OperationAuthorizer $authorizer, private Application $app, private OperationScope $scope) {}

    /** @param (Closure(): ?TrustedPrincipal)|null $beforeAuthorization Trusted HTTP adapter only. */
    public function enter(string $module, ApiOperation $operation, ?Closure $beforeAuthorization = null): OperationContext
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
            $candidate = $this->app->make(LogContext::class)->current()['correlation_id'] ?? null;
            $correlation = is_string($candidate) && preg_match('/\A[a-zA-Z0-9_-]{1,64}\z/D', $candidate) === 1 ? $candidate : $id;
            $context = new OperationContext($module, $operation->key, $id, $parent !== null ? $parent->correlationId : $correlation, $status->generation, $parent?->moduleId);
            $principalScope = $this->app->make(PrincipalScope::class);
            $principal = $beforeAuthorization === null ? $principalScope->current() : $principalScope->resolve($beforeAuthorization);
            if ($operation->permission !== null && ! $principalScope->run($principal, fn (): bool => $principalScope->resolve(fn (): bool => $this->authorizer->allows($context, $operation->permission)))) {
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
