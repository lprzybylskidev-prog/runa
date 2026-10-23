<?php

namespace Runa\Support\Modules\Management;

use Runa\Contracts\Modules\ManagementEntity;
use Runa\Contracts\Modules\ManagementEntityProvider;
use Runa\Contracts\Modules\ManagementProvider;
use Runa\Contracts\Modules\ModuleManagement as ManagementView;
use Runa\Support\Modules\Api\ModuleDeclarations;
use Runa\Support\Modules\Events\ModuleIntegrations;
use Runa\Support\Modules\ModuleAvailability;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\Settings\ModuleSettings;
use Runa\Support\Modules\Work\ModuleWork;
use RuntimeException;
use Throwable;

/** Trusted read boundary. No business action invocation, auth substitute or HTTP exposure. */
final readonly class ModuleManagement
{
    public function __construct(private ModuleRegistry $registry, private ModuleDeclarations $declarations, private ModuleAvailability $availability, private ModuleSettings $settings, private ModuleIntegrations $integrations, private ModuleWork $work, private ManagementRenderers $renderers) {}

    public function read(string $module): ManagementView
    {
        $snapshot = $this->registry->snapshot();
        $record = $snapshot->modules[$module] ?? throw new RuntimeException('Unknown module.');
        $status = $this->availability->statuses($module)[$module];
        $metadata = ['kind' => $record->kind, 'required' => $record->required, 'optional' => $record->optional];
        $settings = null;
        $integrations = $permissions = $actions = $components = $entities = $diagnostics = [];
        if ($record->registered && $record->sourcePresent) {
            try {
                $manifest = $this->declarations->manifest($record);
                $metadata += ['name' => $manifest->definition['name'], 'description' => $manifest->definition['description']];
                foreach ($this->declarations->classes($record, 'management') as $class) {
                    $provider = $this->declarations->make($record, $class, ManagementProvider::class);
                    if (! $provider instanceof ManagementProvider) {
                        throw new RuntimeException;
                    }
                    if ($provider instanceof ManagementEntityProvider) {
                        $declared = $provider->entities();
                        if (! array_is_list($declared) || count($declared) > 32) {
                            throw new RuntimeException;
                        }
                        foreach ($declared as $entity) {
                            if (! $entity instanceof ManagementEntity || isset($entities[$entity->key]) || count($entities) >= 32) {
                                throw new RuntimeException;
                            }
                            $entities[$entity->key] = $entity;
                        }
                    }
                    foreach ($provider->permissions() as $permission) {
                        if (isset($permissions[$permission->key])) {
                            throw new RuntimeException;
                        }
                        $permissions[$permission->key] = $permission;
                    }
                    foreach ($provider->actions() as $action) {
                        $this->renderers->require('action', $action->renderer, $action->rendererVersion);
                        if (isset($actions[$action->key])) {
                            throw new RuntimeException;
                        }
                        $actions[$action->key] = $action;
                    }
                    foreach ($provider->components() as $component) {
                        $this->renderers->require('component', $component->renderer, $component->rendererVersion);
                        if (isset($components[$component->key]) || ! str_starts_with($component->component, $module.'.')) {
                            throw new RuntimeException;
                        }
                        $components[$component->key] = $component;
                    }
                    if (max(count($permissions), count($actions), count($components)) > 128) {
                        throw new RuntimeException;
                    }
                }
                foreach ($entities as $entity) {
                    foreach ([$entity->permission, ...array_map(static fn ($operation): string => $operation->operation->permission ?? throw new RuntimeException('Management permission missing.'), $entity->operations)] as $permission) {
                        if (! isset($permissions[$permission])) {
                            throw new RuntimeException;
                        }
                    }
                }
                foreach ([...array_values($actions), ...array_values($components)] as $entry) {
                    if (! isset($permissions[$entry->permission])) {
                        throw new RuntimeException;
                    }
                }
            } catch (Throwable) {
                $permissions = $actions = $components = $entities = [];
                $diagnostics[] = 'management_declaration_unavailable';
            }
            try {
                if ($this->declarations->classes($record, 'settings') !== []) {
                    $settings = $this->settings->read($module);
                    foreach ($settings['settings'] as $entry) {
                        $this->renderers->require('setting', $entry['definition']->type, 1);
                    }
                }
            } catch (Throwable) {
                $settings = null;
                $diagnostics[] = 'settings_metadata_unavailable';
            }
            try {
                $integrations = $this->integrations->metadata($module);
            } catch (Throwable) {
                $diagnostics[] = 'integration_metadata_unavailable';
            }
        } else {
            $diagnostics[] = 'declaration_source_unavailable';
        }
        $work = $this->work->active($module, 101);
        // Projection is optimistic: never label mixed generations as one coherent snapshot.
        if ($this->registry->snapshot()->generation !== $snapshot->generation || $status->generation !== $snapshot->generation || ($settings !== null && $settings['generation'] !== $snapshot->generation)) {
            throw new RuntimeException('Management snapshot changed; retry.');
        }
        ksort($permissions);
        ksort($actions);
        ksort($components);
        ksort($entities);

        return new ManagementView($module, $snapshot->generation, $status, $metadata, $settings, $integrations, array_slice($work, 0, 100), count($work) > 100, $permissions, $actions, $components, $diagnostics, $entities);
    }
}
