<?php

namespace Runa\Modules\Admin\Queries;

use Closure;
use DateTimeImmutable;
use InvalidArgumentException;
use Runa\Contracts\Modules\ManagementCommandDefinition;
use Runa\Contracts\Modules\ManagementFieldAccess;
use Runa\Contracts\Modules\ManagementOptionsRead;
use Runa\Contracts\Modules\ManagementOptionsReader;
use Runa\Contracts\Modules\ManagementOutcome;
use Runa\Contracts\Modules\ManagementPage;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementRecord;
use Runa\Contracts\Modules\ManagementResourceReader;
use Runa\Contracts\Modules\ManagementSection;
use Runa\Contracts\Modules\ManagementWidget;
use Runa\Contracts\Modules\ManagementWidgetReader;
use Runa\Contracts\Modules\ManagementWrite;
use Runa\Contracts\Modules\ManagementWriter;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\OperationContext;
use Runa\ModuleFramework\Management\InputValues as Values;
use Runa\ModuleFramework\Management\ManagementPages;
use Runa\ModuleFramework\Management\ReadHandlerPlan;
use Runa\Modules\Admin\Actions\AggregatePart;
use Runa\Support\Modules\Api\OperationRejected;
use Throwable;

/** Admin-owned authorization intersection; owners still enforce record Policies.
 *
 * @phpstan-import-type Discovery from ManagementPages
 * @phpstan-import-type OwnerDefinition from ManagementPages
 * @phpstan-import-type WidgetView from ReadHandlerPlan
 *
 * @phpstan-type ReadSelection array{Discovery,string,ManagementSection,\Runa\Contracts\Modules\ManagementBinding,string,ManagementRead,array<string,ManagementFieldAccess>,?ManagementOptionsRead}
 */
final readonly class Projection
{
    public function __construct(private ManagementPages $declarations, private OperationAuthorizer $authorizer) {}

    /** @param list<ReadHandlerPlan> $failedWidgets
     * @param Discovery|null $selectedDiscovery
     * @return array<string, mixed> */
    public function bootstrap(?Closure $invoke = null, array $failedWidgets = [], ?array $selectedDiscovery = null): array
    {
        $discovery = $selectedDiscovery ?? $this->declarations->discover();
        $owners = $widgets = [];
        foreach ($discovery['owners'] as $owner => $definition) {
            $pages = [];
            $default = null;
            foreach ($definition['pages'] as $page) {
                if (! $this->allowed($owner, $page->permission, $discovery['generation'])) {
                    continue;
                }
                $pages[] = ['key' => $page->key, 'title' => $page->title, 'href' => '/admin/'.$owner.'/'.$page->key, 'group' => $page->group, 'order' => $page->order];
                if ($page->default) {
                    $default = $page->key;
                }
            }
            if ($pages !== []) {
                $owners[] = ['id' => $owner, 'title' => $definition['title'], 'default_page' => $default ?? $pages[0]['key'], 'pages' => $pages];
            }
            foreach ($definition['widgets'] as $widget) {
                if (! $this->allowed($owner, $widget->permission, $discovery['generation'])) {
                    continue;
                }
                $projected = $this->widgetView($owner, $widget, $definition);
                try {
                    $binding = $definition['bindings'][$widget->binding];
                    $read = function () use ($owner, $binding) {
                        $handler = $this->declarations->handler($owner, $binding);
                        if (! $handler instanceof ManagementWidgetReader) {
                            throw new InvalidArgumentException('Widget capability unavailable.');
                        }

                        return $handler->widget(new ManagementRead);
                    };
                    $result = $invoke === null ? $read() : $invoke($owner.'.'.$widget->key, $read);
                    $now = new DateTimeImmutable('now');
                    if ($result->observedAt !== null && new DateTimeImmutable($result->observedAt) > $now) {
                        throw new InvalidArgumentException('Future widget observation.');
                    }
                    $projected['values'] = $result->values;
                    $projected['observed_at'] = $result->observedAt;
                    $projected['expires_at'] = $result->expiresAt;
                    if ($result->expiresAt !== null && new DateTimeImmutable($result->expiresAt) <= $now) {
                        $projected['diagnostic'] = 'admin_widget_stale';
                    }
                } catch (Throwable) {
                    $projected['diagnostic'] = 'admin_widget_unavailable';
                }
                $widgets[] = ['order' => $widget->order, 'view' => $projected];
            }
        }
        // A preselected failed widget may return only its source metadata/diagnostic.
        // Current owner availability and its effective grant still govern even
        // diagnostic metadata; an unavailable owner is omitted, never bypassed.
        foreach ($failedWidgets as $plan) {
            $metadata = $plan->widget;
            if ($metadata === null || in_array($plan->id, array_column(array_column($widgets, 'view'), 'id'), true) || ! $this->allowed($metadata['owner'], $metadata['permission'], $discovery['generation'])) {
                continue;
            }
            $view = $metadata['view'];
            $view['diagnostic'] = 'admin_widget_unavailable';
            $widgets[] = ['order' => $metadata['order'], 'view' => $view];
        }
        usort($widgets, static fn (array $a, array $b): int => ($a['order'] <=> $b['order']) ?: strcmp($a['view']['id'], $b['view']['id']));
        $widgets = array_column($widgets, 'view');
        $this->declarations->assertCurrent($discovery['generation']);

        return $this->bounded(['schema_version' => 1, 'generation' => $discovery['generation'],
            'revision' => hash('sha256', serialize([$discovery['revision'], $owners, $widgets])),
            'owners' => $owners, 'dashboard' => ['widgets' => $widgets]]);
    }

    /** @return array<string, mixed> */
    public function page(string $owner, string $key): array
    {
        [$discovery, $page, $sections] = $this->selected($owner, $key);
        $views = [];
        $checks = [];
        foreach ($sections as [$sectionOwner, $section, $identity]) {
            $allowed = $checks[$sectionOwner] ??= $this->permissionChecks($sectionOwner, $discovery['generation']);
            if ($allowed($section->readPermission)) {
                $views[] = $this->section($sectionOwner, $section, $identity, $discovery['generation'], $this->declarations->bindingAvailable($discovery['owners'][$sectionOwner]['bindings'][$section->binding]), $discovery['owners'][$sectionOwner], $allowed);
            }
        }
        $this->declarations->assertCurrent($discovery['generation']);

        return $this->bounded(['schema_version' => 1, 'owner' => $owner, 'key' => $page->key, 'title' => $page->title,
            'generation' => $discovery['generation'], 'revision' => hash('sha256', serialize([$discovery['revision'], $owner, $key, $views])), 'component' => $this->component($discovery['owners'][$owner], $page->component), 'sections' => $views]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  ReadSelection|null  $selectedRead
     * @return array<string, mixed>
     */
    public function read(string $owner, string $key, string $identity, array $query, ?array $selectedRead = null): array
    {
        [$discovery, $sectionOwner, $section, $binding, $kind, $input, $fields, $lookup] = $selectedRead ?? $this->readSelection($owner, $key, $identity, $query);
        try {
            $handler = $this->declarations->handler($sectionOwner, $binding);
            // Handlers enforce native selection/count/export/option authority and record Policies.
            if ($kind === 'record') {
                if (! $handler instanceof ManagementResourceReader) {
                    throw new InvalidArgumentException('Resource capability missing.');
                }
                $result = $this->record($handler->read($input), $fields);
            } elseif ($kind === 'page') {
                if (! $handler instanceof ManagementResourceReader) {
                    throw new InvalidArgumentException('Resource capability missing.');
                }
                $page = $handler->page($input);
                if (count($page->items) > $input->limit) {
                    throw new OperationRejected('admin_result_limit', 'runa.admin', 'section.read');
                }
                $result = ['items' => array_map(fn (ManagementRecord $record): array => $this->record($record, $fields), $page->items), 'next' => $page->next, 'total' => $page->total];
            } else {
                if (! $handler instanceof ManagementOptionsReader) {
                    throw new InvalidArgumentException('Lookup capability missing.');
                }
                $options = $handler->options($lookup);
                if (count($options->items) > $input->limit) {
                    throw new OperationRejected('admin_result_limit', 'runa.admin', 'section.read');
                }
                if ($options->field !== $lookup->field) {
                    throw new OperationRejected('admin_projection_invalid', 'runa.admin', 'section.read');
                }
                $result = ['field' => $options->field, 'items' => $options->items, 'next' => $options->next];
            }
        } catch (OperationRejected $error) {
            throw $error;
        } catch (Throwable) {
            throw new OperationRejected('admin_read_unavailable', 'runa.admin', 'section.read');
        }
        $this->declarations->assertCurrent($discovery['generation']);

        return $this->bounded($result);
    }

    /** @param array<string,mixed> $query
     * @param ReadSelection|null $selectedRead */
    public function readPlan(string $owner, string $key, string $identity, array $query, ?array &$selectedRead = null): ReadHandlerPlan
    {
        [$discovery, $sectionOwner, $section, $binding, $kind, $input, $fields, $lookup] = $this->readSelection($owner, $key, $identity, $query);
        $selectedRead = [$discovery, $sectionOwner, $section, $binding, $kind, $input, $fields, $lookup];
        $definition = $discovery['owners'][$sectionOwner];
        $calls = $this->declarations->readPlan($definition, $binding, $kind === 'record' ? 'read' : $kind, $lookup ?? $input);

        return new ReadHandlerPlan('resource', hash('sha256', serialize([$sectionOwner, $section, $binding, $fields, $kind, $input, $lookup, $calls, $definition['components']])), $calls);
    }

    /** @param Discovery|null $selectedDiscovery
     * @return array<string,ReadHandlerPlan> */
    public function widgetReadPlans(?array &$selectedDiscovery = null): array
    {
        $discovery = $this->declarations->discover();
        $selectedDiscovery = $discovery;
        $plans = [];
        foreach ($discovery['owners'] as $owner => $definition) {
            foreach ($definition['widgets'] as $widget) {
                if (! $this->allowed($owner, $widget->permission, $discovery['generation'])) {
                    continue;
                }
                $binding = $definition['bindings'][$widget->binding];
                $calls = [];
                $diagnostic = null;
                try {
                    $calls = $this->declarations->readPlan($definition, $binding, 'widget', new ManagementRead);
                } catch (Throwable) {
                    $diagnostic = 'admin_read_plan_invalid';
                }
                $id = $owner.'.'.$widget->key;
                $plans[$id] = new ReadHandlerPlan($id, hash('sha256', serialize([$owner, $widget, $binding, $calls, $definition['components'], $diagnostic])), $calls,
                    ['owner' => $owner, 'permission' => $widget->permission, 'order' => $widget->order, 'view' => $this->widgetView($owner, $widget, $definition)], $diagnostic);
            }
        }

        return $plans;
    }

    /** @param OwnerDefinition $definition
     * @return WidgetView */
    private function widgetView(string $owner, ManagementWidget $widget, array $definition): array
    {
        return ['id' => $owner.'.'.$widget->key, 'title' => $widget->title, 'component' => $this->component($definition, $widget->component),
            'values' => [], 'observed_at' => null, 'expires_at' => null, 'diagnostic' => null];
    }

    /** @param array<string,mixed> $query
     * @return ReadSelection */
    private function readSelection(string $owner, string $key, string $identity, array $query): array
    {
        [$discovery, , $sections] = $this->selected($owner, $key);
        [$sectionOwner, $section] = $this->findSection($sections, $identity, $discovery['generation']);
        try {
            if (array_diff(array_keys($query), ['kind', 'field', 'public_id', 'cursor', 'limit', 'filters', 'sort', 'export']) !== []) {
                throw new InvalidArgumentException('Unknown management query.');
            }
            $export = $query['export'] ?? false;
            if (! is_bool($export)) {
                throw new InvalidArgumentException('Invalid export purpose.');
            }
            if ($export && ! $this->allowed($sectionOwner === 'app' ? 'runa.admin' : $sectionOwner,
                'runa.permissions.tables.export', $discovery['generation'])) {
                throw new OperationRejected('module_forbidden', 'runa.admin', 'table.export', 403);
            }
            $lookup = null;
            $kind = $query['kind'] ?? 'record';
            if (! in_array($kind, ['record', 'page', 'options'], true)) {
                throw new InvalidArgumentException('Invalid read kind.');
            }
            if (($kind !== 'options' && array_key_exists('field', $query)) || ($kind === 'options' && array_key_exists('sort', $query))) {
                throw new InvalidArgumentException('Invalid lookup query.');
            }
            $input = new ManagementRead(Values::nullableString($query['public_id'] ?? null), Values::nullableString($query['cursor'] ?? null), Values::integer($query['limit'] ?? 25), Values::filters($query['filters'] ?? []), Values::sort($query['sort'] ?? []));
            $fields = $this->fields($sectionOwner, $section, $discovery['generation']);
            if ($export) {
                $fields = array_filter($fields, static fn (ManagementFieldAccess $field): bool => ! $field->field->sensitive);
            }
            foreach ($input->filters as $field => $value) {
                if (! ($fields[$field]->field->filterable ?? false)) {
                    throw new InvalidArgumentException('Invalid management filter.');
                }
            }
            foreach ($input->sort as $sort) {
                if (! ($fields[$sort['key']]->field->sortable ?? false)) {
                    throw new InvalidArgumentException('Invalid management sort.');
                }
            }
            if ($kind === 'options') {
                $field = $query['field'] ?? null;
                if (! is_string($field) || ! isset($fields[$field]) || $fields[$field]->field->referenceModule === null) {
                    throw new OperationRejected('admin_lookup_unavailable', 'runa.admin', 'section.read', 404);
                }
                $lookup = new ManagementOptionsRead($field, $input->publicId, $input->cursor, $input->limit, $input->filters);
            }
        } catch (InvalidArgumentException|\TypeError) {
            throw new OperationRejected('admin_input_invalid', 'runa.admin', 'section.read', 422);
        }
        $binding = $discovery['owners'][$sectionOwner]['bindings'][$section->binding];

        return [$discovery, $sectionOwner, $section, $binding, $kind, $input, $fields, $lookup];
    }

    /** Future command coordinators share this fail-closed input intersection; no write executes here.
     * @param  array<string, mixed>  $input
     */
    public function writeInput(string $owner, ManagementSection $section, array $input, int $generation): ManagementWrite
    {
        try {
            if (! $this->allowed($owner, $section->readPermission, $generation) || ! $this->allowed($owner, $section->writePermission, $generation)
                || array_diff(array_keys($input), ['command', 'public_id', 'revision', 'values', 'review', 'confirmed']) !== []) {
                throw new InvalidArgumentException('Read-only management section.');
            }
            $write = new ManagementWrite(Values::string($input['command'] ?? ''), Values::nullableString($input['public_id'] ?? null), Values::string($input['revision'] ?? ''), Values::map($input['values'] ?? []), isset($input['review']) ? Values::map($input['review']) : null, Values::boolean($input['confirmed'] ?? false));
            $command = null;
            foreach ($section->commands as $candidate) {
                if ($candidate->key === $write->command) {
                    $command = $candidate;
                }
            }
            if ($command === null || ! $this->allowed($owner, $command->safety->operation->permission, $generation) || ($command->destructive && ! $write->confirmed)
                || array_diff(array_keys($write->values), $command->safety->inputs) !== []) {
                throw new InvalidArgumentException('Management command unavailable.');
            }
            $fields = $this->fields($owner, $section, $generation);
            foreach ($command->safety->inputs as $key) {
                if (! isset($fields[$key]) || ! $fields[$key]->field->writable || ! $this->allowed($owner, $fields[$key]->writePermission, $generation)) {
                    throw new InvalidArgumentException('Read-only management field.');
                }
            }

            return $write;
        } catch (InvalidArgumentException|\TypeError) {
            throw new OperationRejected('admin_input_invalid', 'runa.admin', 'command', 422);
        }
    }

    /** Resolve an independent command under entry admission; the owning writer runs after it ends.
     *
     * @param  array<string,mixed>  $input
     * @return array{writer:ManagementWriter,input:ManagementWrite,command:ManagementCommandDefinition,fields:array<string,ManagementFieldAccess>}
     */
    public function prepareCommand(string $owner, string $page, string $identity, array $input, bool $preview = false): array
    {
        [$discovery, , $sections] = $this->selected($owner, $page);
        [$sectionOwner, $section] = $this->findSection($sections, $identity, $discovery['generation']);
        if (! $this->declarations->bindingAvailable($discovery['owners'][$sectionOwner]['bindings'][$section->binding])) {
            throw new OperationRejected('admin_binding_unavailable', 'runa.admin', 'command', 409);
        }
        // A preview does not confirm a destructive operation or require a prior review.
        $candidate = $preview ? array_replace($input, ['confirmed' => true]) : $input;
        $write = $this->writeInput($sectionOwner, $section, $candidate, $discovery['generation']);
        $command = array_values(array_filter($section->commands, fn ($command): bool => $command->key === $write->command))[0];
        if (! $preview && $command->safety->previewRequired && $write->review === null) {
            throw new OperationRejected('admin_review_required', 'runa.admin', 'command', 409);
        }
        $writer = $this->declarations->handler($sectionOwner, $discovery['owners'][$sectionOwner]['bindings'][$command->binding]);
        if (! $writer instanceof ManagementWriter) {
            throw new OperationRejected('admin_operation_unavailable', 'runa.admin', 'command', 404);
        }
        $this->declarations->assertCurrent($discovery['generation']);

        return ['writer' => $writer, 'input' => $write, 'command' => $command,
            'fields' => $this->fields($sectionOwner, $section, $discovery['generation'])];
    }

    /** The complete current writable atomic Save set, including editable contributions.
     *
     * @param  array<string,mixed>  $input
     * @return array{revision:string,parts:list<AggregatePart>}
     */
    public function prepareAggregate(string $owner, string $page, array $input, bool $preview): array
    {
        if (array_diff(array_keys($input), ['revision', 'participants']) !== []
            || ! is_string($input['revision'] ?? null) || strlen($input['revision']) > 256
            || ! is_array($input['participants'] ?? null) || ! array_is_list($input['participants'])
            || count($input['participants']) < 1 || count($input['participants']) > 15) {
            throw new OperationRejected('admin_input_invalid', 'runa.admin', 'save', 422);
        }
        $submitted = [];
        foreach ($input['participants'] as $item) {
            if (! is_array($item) || array_diff(array_keys($item), ['id', 'input']) !== []
                || ! is_string($item['id'] ?? null) || isset($submitted[$item['id']]) || ! is_array($item['input'] ?? null)) {
                throw new OperationRejected('admin_input_invalid', 'runa.admin', 'save', 422);
            }
            $submitted[$item['id']] = Values::map($item['input']);
        }
        [$discovery, , $sections] = $this->selected($owner, $page);
        $parts = $checks = [];
        foreach ($sections as [$sectionOwner, $section, $id]) {
            $allowed = $checks[$sectionOwner] ??= $this->permissionChecks($sectionOwner, $discovery['generation']);
            if (! $allowed($section->readPermission)) {
                continue;
            }
            $definition = $discovery['owners'][$sectionOwner];
            $view = $this->section($sectionOwner, $section, $id, $discovery['generation'],
                $this->declarations->bindingAvailable($definition['bindings'][$section->binding]), $definition, $allowed);
            $saves = array_values(array_filter($view['commands'], fn ($command): bool => $command['intent'] === 'save' && $command['mode'] === 'atomic'));
            if ($saves === []) {
                continue;
            }
            if (! isset($submitted[$id]) || ! $saves[0]['available']) {
                throw new OperationRejected('admin_participants_changed', 'runa.admin', 'save', 409);
            }
            $candidate = $preview ? array_replace($submitted[$id], ['confirmed' => true]) : $submitted[$id];
            $write = $this->writeInput($sectionOwner, $section, $candidate, $discovery['generation']);
            if ($write->command !== $saves[0]['key']) {
                throw new OperationRejected('admin_input_invalid', 'runa.admin', 'save', 422);
            }
            $command = array_values(array_filter($section->commands, fn ($command): bool => $command->key === $write->command))[0];
            $binding = $definition['bindings'][$command->binding];
            $writer = $this->declarations->handler($sectionOwner, $binding);
            if (! $writer instanceof ManagementWriter || $section->view !== 'record' || $command->safety->bulk) {
                throw new OperationRejected('admin_participant_incompatible', 'runa.admin', 'save', 409);
            }
            $record = $this->read($owner, $page, $id, ['public_id' => $write->publicId]);
            if ($record['public_id'] !== $write->publicId || ! hash_equals(Values::string($record['revision']), $write->revision)) {
                throw new OperationRejected('admin_revision_stale', 'runa.admin', 'save', 409);
            }
            $parts[] = new AggregatePart($sectionOwner, $id, $command, $write, $writer,
                $this->declarations->participant($sectionOwner, $binding), $this->fields($sectionOwner, $section, $discovery['generation']));
            unset($submitted[$id]);
        }
        if ($submitted !== [] || $parts === []) {
            throw new OperationRejected('admin_participants_changed', 'runa.admin', 'save', 409);
        }
        usort($parts, fn ($a, $b): int => strcmp($a->id, $b->id));
        $this->declarations->assertCurrent($discovery['generation']);

        return ['revision' => Values::string($this->page($owner, $page)['revision']), 'parts' => $parts];
    }

    /**
     * @param  array<string,ManagementFieldAccess>  $fields
     * @return array<string,mixed>
     */
    public function outcome(ManagementOutcome $outcome, array $fields): array
    {
        return $this->bounded(['status' => $outcome->status,
            'record' => $outcome->record === null ? null : $this->record($outcome->record, $fields),
            'operation_id' => $outcome->operationId]);
    }

    /** Reauthorize returned values after an independent native mutation.
     * @return array<string,mixed>
     */
    public function commandOutcome(string $owner, string $page, string $identity, ManagementOutcome $outcome): array
    {
        [$discovery, , $sections] = $this->selected($owner, $page);
        [$sectionOwner, $section] = $this->findSection($sections, $identity, $discovery['generation']);

        return $this->outcome($outcome, $this->fields($sectionOwner, $section, $discovery['generation']));
    }

    /** @return array{Discovery,ManagementPage,list<array{string,ManagementSection,string}>} */
    private function selected(string $owner, string $key): array
    {
        $discovery = $this->declarations->discover();
        $page = $discovery['owners'][$owner]['pages'][$key] ?? null;
        if (! $page instanceof ManagementPage || ! $this->allowed($owner, $page->permission, $discovery['generation'])) {
            throw new OperationRejected('admin_page_unavailable', 'runa.admin', 'page', 404);
        }
        $sections = [];
        foreach ($page->sections as $section) {
            $sections[] = [$owner, $section, $section->key];
        }
        $contributions = [];
        foreach ($discovery['owners'] as $contributor => $definition) {
            foreach ($definition['contributions'] as $contribution) {
                foreach ($page->sections as $host) {
                    if ($contribution->host === $owner.'.'.$key.'.'.$host->key && $this->allowed($owner, $host->readPermission, $discovery['generation'])) {
                        $identity = $contributor.'.'.$contribution->key.'.'.$contribution->section->key;
                        if (in_array($identity, array_column($sections, 2), true)) {
                            throw new OperationRejected('admin_declaration_unavailable', 'runa.admin', 'page');
                        }
                        $contributions[] = ['order' => $contribution->order, 'id' => $identity, 'section' => [$contributor, $contribution->section, $identity]];
                    }
                }
            }
        }
        usort($sections, static fn (array $a, array $b): int => ($a[1]->order <=> $b[1]->order) ?: strcmp($a[2], $b[2]));
        usort($contributions, static fn (array $a, array $b): int => ($a['order'] <=> $b['order']) ?: strcmp($a['id'], $b['id']));

        return [$discovery, $page, [...$sections, ...array_column($contributions, 'section')]];
    }

    /**
     * @param  list<array{string,ManagementSection,string}>  $sections
     * @return array{string,ManagementSection}
     */
    private function findSection(array $sections, string $identity, int $generation): array
    {
        foreach ($sections as [$owner, $section, $id]) {
            if ($id === $identity && $this->allowed($owner, $section->readPermission, $generation)) {
                return [$owner, $section];
            }
        }
        throw new OperationRejected('admin_page_unavailable', 'runa.admin', 'section.read', 404);
    }

    /**
     * @param  OwnerDefinition  $ownerDefinition
     * @param  Closure(?string): bool  $allowed
     * @return array{commands:list<array{key:string,intent:string,mode:string,available:bool}>,...<string,mixed>}
     */
    private function section(string $owner, ManagementSection $section, string $identity, int $generation, bool $available, array $ownerDefinition, Closure $allowed): array
    {
        $readonly = ! $available || ! $allowed($section->writePermission);
        $fields = $this->fields($owner, $section, $generation, $allowed);
        $views = $commands = [];
        foreach ($fields as $field) {
            $definition = $field->field;
            $views[] = ['key' => $definition->key, 'type' => $definition->type, 'label' => $definition->names, 'description' => $definition->descriptions,
                'readonly' => $readonly || ! $definition->writable || ! $allowed($field->writePermission),
                'sensitive' => $definition->sensitive, 'filterable' => $definition->filterable, 'sortable' => $definition->sortable,
                'lookup' => $definition->referenceModule !== null, 'required' => $definition->required, 'max_length' => $definition->maxLength,
                'choices' => $definition->choices, 'multiple' => $definition->multiple, 'component' => $this->component($ownerDefinition, $field->component)];
        }
        foreach ($section->commands as $command) {
            if ($readonly || ! $allowed($command->safety->operation->permission)) {
                continue;
            }
            foreach ($command->safety->inputs as $key) {
                if (! isset($fields[$key]) || ! $fields[$key]->field->writable || ! $allowed($fields[$key]->writePermission)) {
                    continue 2;
                }
            }
            $commands[] = ['key' => $command->key, 'title' => $command->title, 'mode' => $command->mode, 'destructive' => $command->destructive,
                'requirement' => $command->safety->stepUp, 'preview_required' => $command->safety->previewRequired,
                'inputs' => $command->safety->inputs, 'bulk' => $command->safety->bulk,
                'intent' => $command->intent,
                'action' => $command->proofAction,
                'available' => $this->declarations->bindingAvailable($ownerDefinition['bindings'][$command->binding])];
        }

        return ['export_allowed' => $available && $allowed('runa.permissions.tables.export'), 'key' => $identity, 'title' => $section->title, 'view' => $section->view, 'readonly' => $readonly, 'required' => $section->required,
            'component' => $this->component($ownerDefinition, $section->component), 'fields' => $views, 'commands' => $commands, 'record' => null, 'diagnostic' => $available ? null : 'admin_binding_unavailable'];
    }

    /**
     * @param  array{components:array<string,array{key:string,owner:string,manifest_hash:?string,build_id:?string}>}  $definition
     * @return array{key:string,owner:string,manifest_hash:?string,build_id:?string}|null
     */
    private function component(array $definition, ?string $key): ?array
    {
        return $key === null ? null : $definition['components'][$key];
    }

    /**
     * @param  (Closure(?string): bool)|null  $allowed
     * @return array<string,ManagementFieldAccess>
     */
    private function fields(string $owner, ManagementSection $section, int $generation, ?Closure $allowed = null): array
    {
        $allowed ??= $this->permissionChecks($owner, $generation);
        $fields = [];
        foreach ($section->fields as $field) {
            if ($allowed($field->readPermission)) {
                $fields[$field->field->key] = $field;
            }
        }

        return $fields;
    }

    /** Deduplicate one synchronous declaration projection, never an owning read/write.
     * @return Closure(?string): bool
     */
    private function permissionChecks(string $owner, int $generation): Closure
    {
        $decisions = [];

        return function (?string $permission) use ($owner, $generation, &$decisions): bool {
            if ($permission === null) {
                return false;
            }

            return $decisions[$permission] ??= $this->allowed($owner, $permission, $generation);
        };
    }

    /**
     * @param  array<string,ManagementFieldAccess>  $fields
     * @return array<string,mixed>
     */
    private function record(ManagementRecord $record, array $fields): array
    {
        $values = [];
        foreach ($fields as $key => $access) {
            if (! array_key_exists($key, $record->values)) {
                continue;
            }
            $value = $record->values[$key];
            if ($access->field->sensitive) {
                // A handler must return state explicitly. Never infer or serialize a raw secret.
                if (! is_array($value) || array_keys($value) !== ['set'] || ! is_bool($value['set'])) {
                    throw new OperationRejected('admin_projection_invalid', 'runa.admin', 'section.read');
                }
            } elseif ($value !== null) {
                $valid = match ($access->field->type) {
                    'text' => is_string($value) && ($access->field->maxLength === null || mb_strlen($value) <= $access->field->maxLength),
                    'boolean' => is_bool($value),
                    'integer' => is_int($value),
                    'datetime' => is_string($value) && preg_match('/\\A[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(?:\\.[0-9]+)?(?:Z|\\+00:00)\\z/D', $value) === 1,
                    'dictionary' => is_string($value) && ($access->field->choices === [] || in_array($value, $access->field->choices, true)),
                    'uuid' => $this->references($value, $access->field->multiple),
                    default => false,
                };
                if (! $valid) {
                    throw new OperationRejected('admin_projection_invalid', 'runa.admin', 'section.read');
                }
            }
            $values[$key] = $value;
        }

        return ['public_id' => $record->publicId, 'revision' => $record->revision, 'values' => $values];
    }

    private function references(mixed $value, bool $multiple): bool
    {
        $values = $multiple ? $value : [$value];
        if (! is_array($values) || ! array_is_list($values) || count($values) > 100) {
            return false;
        }
        foreach ($values as $id) {
            if (! is_string($id) || preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/Di', $id) !== 1) {
                return false;
            }
        }

        return true;
    }

    private function allowed(string $owner, ?string $permission, int $generation): bool
    {
        return $permission !== null && $this->authorizer->allows(new OperationContext($owner === 'app' ? 'runa.admin' : $owner, 'admin.read', 'admin', 'admin', $generation), $permission);
    }

    /**
     * @param  array<string,mixed>  $result
     * @return array<string,mixed>
     */
    private function bounded(array $result): array
    {
        if (strlen(json_encode($result, JSON_THROW_ON_ERROR)) > 1048576) {
            throw new OperationRejected('admin_result_limit', 'runa.admin', 'projection');
        }

        return $result;
    }
}
