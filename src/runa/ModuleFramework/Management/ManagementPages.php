<?php

namespace Runa\ModuleFramework\Management;

use InvalidArgumentException;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Contracts\Modules\ManagementBinding;
use Runa\Contracts\Modules\ManagementContribution;
use Runa\Contracts\Modules\ManagementHandler;
use Runa\Contracts\Modules\ManagementOptionsReader;
use Runa\Contracts\Modules\ManagementPage;
use Runa\Contracts\Modules\ManagementPageProvider;
use Runa\Contracts\Modules\ManagementParticipant;
use Runa\Contracts\Modules\ManagementProvider;
use Runa\Contracts\Modules\ManagementResourceReader;
use Runa\Contracts\Modules\ManagementSection;
use Runa\Contracts\Modules\ManagementWidget;
use Runa\Contracts\Modules\ManagementWidgetReader;
use Runa\Contracts\Modules\ManagementWriter;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleDiscovery;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\ModuleRecord;
use Throwable;

/** Internal discovery of additive declarations. No handler invocation or authorization. */
final readonly class ManagementPages
{
    public function __construct(private ModuleRegistry $registry, private ModuleDeclarations $declarations,
        private ModuleAvailability $availability, private ApplicationManagement $application,
        private ModuleApis $apis, private string $root, private bool $compiledOnly = false) {}

    /** @return array{generation:int,revision:string,owners:array<string, array{title:string|array{pl:string,en:string},pages:array<string,ManagementPage>,bindings:array<string,ManagementBinding>,contributions:array<string,ManagementContribution>,widgets:array<string,ManagementWidget>,components:array<string,array{key:string,owner:string,manifest_hash:?string,build_id:?string}>}>,diagnostics:array<string,string>} */
    public function discover(): array
    {
        $snapshot = $this->registry->snapshot();
        $statuses = $this->availability->statuses();
        $owners = $diagnostics = [];
        $hashes = [];
        foreach ($snapshot->modules as $record) {
            if (! $record->registered || ! $record->sourcePresent || ! ($statuses[$record->id]->available ?? false)) {
                continue;
            }
            // Semantic source/policy state, excluding successful observation publication.
            $hashes[$record->id] = [$record->publicId, $record->manifestHash, $record->installedVersion];
            try {
                $providers = [];
                foreach ($this->declarations->classes($record, 'management') as $class) {
                    $provider = $this->declarations->make($record, $class, ManagementProvider::class);
                    if ($provider instanceof ManagementPageProvider) {
                        $providers[] = $provider;
                    }
                }
                if ($providers !== []) {
                    $manifest = $this->declarations->manifest($record);
                    /** @var array{pl:string,en:string} $names */
                    $names = (array) $manifest->definition['name'];
                    $owners[$record->id] = $this->collect($record->id, $providers, $names, $record);
                }
            } catch (Throwable) {
                // Discard the entire conflicting owner; another owner cannot be shadowed.
                $diagnostics[$record->id] = 'admin_declaration_unavailable';
            }
        }
        $providers = $this->application->providers();
        if ($providers !== []) {
            try {
                $owners['app'] = $this->collect('app', $providers, 'Application', null);
            } catch (Throwable) {
                $diagnostics['app'] = 'admin_declaration_unavailable';
            }
        }
        foreach ($statuses as $status) {
            if ($status->generation !== $snapshot->generation) {
                throw new OperationRejected('availability_changed', 'runa.admin', 'discovery', 409);
            }
        }
        $this->assertCurrent($snapshot->generation);
        ksort($owners);
        ksort($hashes);

        return ['generation' => $snapshot->generation,
            'revision' => hash('sha256', serialize([$hashes, $owners])),
            'owners' => $owners, 'diagnostics' => $diagnostics];
    }

    public function assertCurrent(int $generation): void
    {
        if ($this->registry->snapshot()->generation !== $generation) {
            throw new OperationRejected('availability_changed', 'runa.admin', 'discovery', 409);
        }
    }

    public function handler(string $owner, ManagementBinding $binding): ManagementHandler
    {
        if (! $this->bindingAvailable($binding)) {
            throw new OperationRejected('admin_binding_unavailable', 'runa.admin', 'section.read');
        }
        $record = $owner === 'app' ? null : ($this->registry->snapshot()->modules[$owner] ?? throw new InvalidArgumentException('Unknown management owner.'));
        $instance = $record === null ? $this->application->make($binding->handler)
            : $this->declarations->make($record, $binding->handler, ManagementHandler::class);

        return $instance instanceof ManagementHandler ? $instance : throw new InvalidArgumentException('Invalid management handler.');
    }

    public function participant(string $owner, ManagementBinding $binding): ManagementParticipant
    {
        if ($binding->participant === null || ! $this->bindingAvailable($binding)) {
            throw new OperationRejected('admin_participant_unavailable', 'runa.admin', 'save', 409);
        }
        $record = $owner === 'app' ? null : ($this->registry->snapshot()->modules[$owner] ?? throw new InvalidArgumentException('Unknown management owner.'));
        $instance = $record === null ? $this->application->make($binding->participant)
            : $this->declarations->make($record, $binding->participant, ManagementParticipant::class);

        return $instance instanceof ManagementParticipant ? $instance : throw new InvalidArgumentException('Invalid management participant.');
    }

    /** @param list<ManagementPageProvider> $providers
     * @param  string|array{pl:string,en:string}  $title
     * @return array{title:string|array{pl:string,en:string},pages:array<string,ManagementPage>,bindings:array<string,ManagementBinding>,contributions:array<string,ManagementContribution>,widgets:array<string,ManagementWidget>,components:array<string,array{key:string,owner:string,manifest_hash:?string,build_id:?string}>}
     */
    private function collect(string $owner, array $providers, string|array $title, ?ModuleRecord $record): array
    {
        $components = [];
        $result = ['title' => $title, 'pages' => [], 'bindings' => [], 'contributions' => [], 'widgets' => []];
        foreach ($providers as $provider) {
            foreach (['pages' => [ManagementPage::class, 32], 'bindings' => [ManagementBinding::class, 128],
                'contributions' => [ManagementContribution::class, 64], 'widgets' => [ManagementWidget::class, 32]] as $kind => [$type, $limit]) {
                $items = $provider->$kind();
                DeclarationRules::collection($items, $type, $limit);
                foreach ($items as $item) {
                    if (isset($result[$kind][$item->key]) || count($result[$kind]) >= $limit) {
                        throw new InvalidArgumentException('Conflicting management declarations.');
                    }
                    $result[$kind][$item->key] = $item;
                }
            }
        }
        if (count(array_filter($result['pages'], static fn (ManagementPage $page): bool => $page->default)) > 1) {
            throw new InvalidArgumentException('Duplicate default management page.');
        }
        foreach ($result['bindings'] as $binding) {
            $this->binding($owner, $binding, $record);
        }
        $section = function (ManagementSection $section) use ($owner, $record, $result, &$components): void {
            $this->captureComponent($components, $owner, $section->component, $record);
            if (! isset($result['bindings'][$section->binding])) {
                throw new InvalidArgumentException('Management section binding missing.');
            }
            $this->capability($owner, $result['bindings'][$section->binding], ManagementResourceReader::class, $record);
            foreach ($section->fields as $field) {
                if ($field->field->referenceModule !== null) {
                    $this->capability($owner, $result['bindings'][$section->binding], ManagementOptionsReader::class, $record);
                }
                $this->captureComponent($components, $owner, $field->component, $record);
                if ($field->field->referenceModule !== null) {
                    $this->dependency($owner, $record, $field->field->referenceModule);
                }
            }
            foreach ($section->commands as $command) {
                $binding = $result['bindings'][$command->binding] ?? throw new InvalidArgumentException('Management command binding missing.');
                $this->capability($owner, $binding, ManagementWriter::class, $record);
                if ($command->participant !== $binding->participant) {
                    throw new InvalidArgumentException('Management participant binding mismatch.');
                }
            }
        };
        foreach ($result['pages'] as $page) {
            $this->captureComponent($components, $owner, $page->component, $record);
            foreach ($page->sections as $item) {
                $section($item);
            }
        }
        foreach ($result['contributions'] as $contribution) {
            if ($contribution->host === 'runa.admin.dashboard.widgets') {
                throw new InvalidArgumentException('Dashboard host accepts widgets only.');
            }
            if (str_starts_with($owner, 'runa.') && ! str_starts_with($contribution->host, 'runa.')) {
                throw new InvalidArgumentException('Invalid management contribution direction.');
            }
            if ($record !== null) {
                $parts = explode('.', $contribution->host);
                $this->dependency($owner, $record, $parts[0].'.'.$parts[1]);
            }
            $section($contribution->section);
        }
        foreach ($result['widgets'] as $widget) {
            $this->captureComponent($components, $owner, $widget->component, $record);
            if (! isset($result['bindings'][$widget->binding])) {
                throw new InvalidArgumentException('Management widget binding missing.');
            }
            $this->capability($owner, $result['bindings'][$widget->binding], ManagementWidgetReader::class, $record);
        }
        foreach (['pages', 'contributions', 'widgets'] as $kind) {
            uasort($result[$kind], static fn ($a, $b): int => ($a->order <=> $b->order) ?: strcmp($a->key, $b->key));
        }
        ksort($result['bindings']);
        ksort($components);
        $result['components'] = $components;

        return $result;
    }

    private function binding(string $owner, ManagementBinding $binding, ?ModuleRecord $record): void
    {
        $class = $record === null ? $this->application->inspect($binding->handler) : $this->declarations->inspect($record, $binding->handler);
        if (! $class->isInstantiable() || ! $class->implementsInterface(ManagementHandler::class) || ($record !== null && $binding->targets === []) || ($record === null && ! $this->application->bound($binding->handler))) {
            throw new InvalidArgumentException('Invalid management handler binding.');
        }
        if ($binding->participant !== null) {
            $participant = $record === null ? $this->application->inspect($binding->participant) : $this->declarations->inspect($record, $binding->participant);
            if (! $participant->isInstantiable() || ! $participant->implementsInterface(ManagementParticipant::class)
                || ($record === null && ! $this->application->bound($binding->participant))) {
                throw new InvalidArgumentException('Invalid management participant binding.');
            }
        }
        foreach ($binding->targets as $target) {
            $this->dependency($owner, $record, $target->module);
            $targetRecord = $this->registry->snapshot()->modules[$target->module] ?? null;
            if ($targetRecord === null || ! $targetRecord->registered || ! $targetRecord->sourcePresent) {
                // Optional source absence is runtime availability, never owner invalidity.
                if ($record !== null && ! isset($record->optional[$target->module])) {
                    throw new InvalidArgumentException('Management target missing.');
                }

                continue;
            }
            $found = false;
            foreach ($this->declarations->classes($targetRecord, 'apis') as $declaration) {
                $provider = $this->declarations->make($targetRecord, $declaration, ApiProvider::class);
                if (! $provider instanceof ApiProvider) {
                    throw new InvalidArgumentException('Invalid management API provider.');
                }
                $api = $provider->definition();
                if ($api->contract === $target->contract && $api->version === $target->version) {
                    if (array_diff($target->methods, array_keys($api->operations)) !== []) {
                        throw new InvalidArgumentException('Undeclared management API method.');
                    }
                    $found = true;
                }
            }
            if (! $found) {
                throw new InvalidArgumentException('Management API target missing.');
            }
        }
    }

    /** Current binding authority still belongs to the guarded owning operation. */
    public function bindingAvailable(ManagementBinding $binding): bool
    {
        foreach ($binding->targets as $target) {
            if (! ($this->availability->statuses($target->module)[$target->module]->available ?? false)) {
                return false;
            }
        }

        return true;
    }

    private function capability(string $owner, ManagementBinding $binding, string $contract, ?ModuleRecord $record): void
    {
        $class = $record === null ? $this->application->inspect($binding->handler) : $this->declarations->inspect($record, $binding->handler);
        if (! $class->implementsInterface($contract)) {
            throw new InvalidArgumentException('Management binding capability missing.');
        }
    }

    private function dependency(string $owner, ?ModuleRecord $record, string $target): void
    {
        if ($record !== null && $target !== $owner && ! isset($record->required[$target]) && ! isset($record->optional[$target])) {
            throw new InvalidArgumentException('Undeclared management dependency.');
        }
    }

    /** @param array<string,array{key:string,owner:string,manifest_hash:?string,build_id:?string}> $components */
    private function captureComponent(array &$components, string $owner, ?string $key, ?ModuleRecord $record): void
    {
        $this->component($owner, $key, $record);
        if ($key !== null && ! isset($components[$key])) {
            $directory = $this->root.'/'.($record === null ? 'app/resources' : $record->sourcePath.'/frontend');
            $components[$key] = (new ManagementBuilds($this->root, $this->compiledOnly))->component($owner, $key, $directory, $record?->manifestHash);
        }
    }

    private function component(string $owner, ?string $component, ?ModuleRecord $record): void
    {
        if ($component === null) {
            return;
        }
        $prefix = $owner.'.';
        $key = substr($component, strlen($prefix));
        if (! str_starts_with($component, $prefix) || preg_match('/\A[a-z][a-z0-9_]*\z/D', $key) !== 1) {
            throw new InvalidArgumentException('Invalid management component ownership.');
        }
        if ($this->compiledOnly) {
            return;
        }
        $relative = $record === null ? 'app/resources/js/components/admin/'.$key.'.vue' : $record->sourcePath.'/frontend/components/'.$key.'.vue';
        $file = ModuleDiscovery::ownedPath($this->root, $this->root.'/'.$relative);
        if (! is_file($file)) {
            throw new InvalidArgumentException('Management build component missing.');
        }
    }
}
