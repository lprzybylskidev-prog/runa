<?php

namespace Runa\Integration\Core;

use Runa\Contracts\Modules\ApiOperation;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\Management\ModuleManagement;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Support\Modules\Api\OperationRejected;

/** Trusted fixed read adapter. Settings/work/configuration objects never cross it. */
final readonly class AdminDiagnostics
{
    public function __construct(private AdminEntry $entry, private OperationGuard $guard,
        private ModuleRegistry $registry, private ModuleAvailability $availability, private ModuleManagement $management) {}

    /** @return array<string, mixed> */
    public function snapshot(string $filter = 'enabled'): array
    {
        if (! in_array($filter, ['enabled', 'registered', 'all'], true)) {
            throw new OperationRejected('admin_filter_invalid', 'runa.admin', 'diagnostics.read', 422);
        }

        return $this->entry->read(function () use ($filter): array {
            $context = $this->guard->enter('runa.admin', new ApiOperation('diagnostics.read', 'runa.admin.diagnostics.read'));
            $snapshot = $this->registry->snapshot();
            $statuses = $this->availability->statuses(); // Cached observations only; never diagnose on read.
            $modules = [];
            foreach ($snapshot->modules as $record) {
                if (($filter === 'enabled' && (! $record->registered || ! $record->enabled))
                    || ($filter === 'registered' && ! $record->registered)) {
                    continue;
                }
                $status = $statuses[$record->id] ?? throw new OperationRejected('availability_changed', 'runa.admin', 'diagnostics.read', 409);
                $modules[] = [
                    'id' => $record->id, 'kind' => $record->kind,
                    'registered' => $record->registered, 'enabled' => $record->enabled,
                    'source_present' => $record->sourcePresent, 'source_version' => $record->sourceVersion,
                    'installed_version' => $record->installedVersion, 'available' => $status->available,
                    'health' => $status->ownHealth,
                    'required' => $record->required, 'optional' => $record->optional,
                    // Native blocker paths and diagnostic payloads stay private.
                    'diagnostic' => $status->available ? null : 'module_unavailable',
                    'observations' => array_map(static fn (array $value): array => [
                        'passed' => $value['passed'],
                        'observed_at' => gmdate('Y-m-d\TH:i:s\Z', $value['checked_at']),
                        'expires_at' => gmdate('Y-m-d\TH:i:s\Z', $value['expires_at']),
                    ], array_values($record->healthObservations)),
                ];
            }
            $this->guard->assertCurrent($context);
            if ($context->generation !== $snapshot->generation
                || array_any($statuses, static fn ($status): bool => $status->generation !== $snapshot->generation)) {
                throw new OperationRejected('availability_changed', 'runa.admin', 'diagnostics.read', 409);
            }
            $result = ['generation' => $snapshot->generation, 'modules' => $modules];
            if (strlen(json_encode($result, JSON_THROW_ON_ERROR)) > 1048576) {
                throw new OperationRejected('admin_projection_limit', 'runa.admin', 'diagnostics.read', 409);
            }

            return $result;
        });
    }

    /** @return array<string, mixed> Safe declaration diagnostics only; no metadata/value passthrough. */
    public function module(string $module): array
    {
        return $this->entry->read(function () use ($module): array {
            $context = $this->guard->enter('runa.admin', new ApiOperation('diagnostics.read', 'runa.admin.diagnostics.read'));
            if (! isset($this->registry->snapshot()->modules[$module])) {
                throw new OperationRejected('admin_module_unknown', 'runa.admin', 'diagnostics.read', 404);
            }
            $view = $this->management->read($module);
            $this->guard->assertCurrent($context);
            if ($view->generation !== $context->generation) {
                throw new OperationRejected('availability_changed', 'runa.admin', 'diagnostics.read', 409);
            }

            return ['id' => $module, 'generation' => $view->generation,
                'diagnostics' => array_values(array_intersect($view->diagnostics, [
                    'management_declaration_unavailable', 'settings_metadata_unavailable',
                    'integration_metadata_unavailable', 'declaration_source_unavailable',
                ]))];
        });
    }
}
