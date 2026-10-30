<?php

namespace Runa\Modules\Admin\Queries;

use DateTimeImmutable;
use InvalidArgumentException;
use Runa\Contracts\Modules\ManagementFieldAccess;
use Runa\Contracts\Modules\ManagementOptionsRead;
use Runa\Contracts\Modules\ManagementOptionsReader;
use Runa\Contracts\Modules\ManagementPage;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementRecord;
use Runa\Contracts\Modules\ManagementResourceReader;
use Runa\Contracts\Modules\ManagementSection;
use Runa\Contracts\Modules\ManagementWidgetReader;
use Runa\Contracts\Modules\ManagementWrite;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\OperationContext;
use Runa\ModuleFramework\Management\ManagementPages;
use Runa\Support\Modules\Api\OperationRejected;
use Throwable;

/** Admin-owned authorization intersection; owners still enforce record Policies. */
final readonly class Projection
{
    public function __construct(private ManagementPages $declarations, private OperationAuthorizer $authorizer) {}

    /** @return array<string, mixed> */
    public function bootstrap(): array
    {
        $discovery = $this->declarations->discover();
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
                $projected = ['id' => $owner.'.'.$widget->key, 'title' => $widget->title, 'component' => $this->component($definition, $widget->component),
                    'values' => [], 'observed_at' => null, 'expires_at' => null, 'diagnostic' => null];
                try {
                    $binding = $definition['bindings'][$widget->binding];
                    $handler = $this->declarations->handler($owner, $binding);
                    if (! $handler instanceof ManagementWidgetReader) {
                        throw new InvalidArgumentException('Widget capability unavailable.');
                    }
                    $result = $handler->widget(new ManagementRead);
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
        foreach ($sections as [$sectionOwner, $section, $identity]) {
            if ($this->allowed($sectionOwner, $section->readPermission, $discovery['generation'])) {
                $views[] = $this->section($sectionOwner, $section, $identity, $discovery['generation'], $this->declarations->bindingAvailable($discovery['owners'][$sectionOwner]['bindings'][$section->binding]), $discovery['owners'][$sectionOwner]);
            }
        }
        $this->declarations->assertCurrent($discovery['generation']);

        return $this->bounded(['schema_version' => 1, 'owner' => $owner, 'key' => $page->key, 'title' => $page->title,
            'generation' => $discovery['generation'], 'revision' => hash('sha256', serialize([$discovery['revision'], $owner, $key, $views])), 'component' => $this->component($discovery['owners'][$owner], $page->component), 'sections' => $views]);
    }

    /** @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function read(string $owner, string $key, string $identity, array $query): array
    {
        [$discovery, , $sections] = $this->selected($owner, $key);
        [$sectionOwner, $section] = $this->findSection($sections, $identity, $discovery['generation']);
        try {
            if (array_diff(array_keys($query), ['kind', 'field', 'public_id', 'cursor', 'limit', 'filters', 'sort']) !== []) {
                throw new InvalidArgumentException('Unknown management query.');
            }
            $lookup = null;
            $kind = $query['kind'] ?? 'record';
            if (! in_array($kind, ['record', 'page', 'options'], true)) {
                throw new InvalidArgumentException('Invalid read kind.');
            }
            if (($kind !== 'options' && array_key_exists('field', $query)) || ($kind === 'options' && array_key_exists('sort', $query))) {
                throw new InvalidArgumentException('Invalid lookup query.');
            }
            $input = new ManagementRead($query['public_id'] ?? null, $query['cursor'] ?? null, $query['limit'] ?? 25, $query['filters'] ?? [], $query['sort'] ?? []);
            $fields = $this->fields($sectionOwner, $section, $discovery['generation']);
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
                if (! $handler instanceof ManagementOptionsReader || $lookup === null) {
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
            $write = new ManagementWrite($input['command'] ?? '', $input['public_id'] ?? null, $input['revision'] ?? '', $input['values'] ?? [], $input['review'] ?? null, $input['confirmed'] ?? false);
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

    /** @return array{array<string,mixed>,ManagementPage,list<array{string,ManagementSection,string}>} */
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

    /** @param list<array{string,ManagementSection,string}> $sections
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

    /** @param array{components:array<string,array{key:string,owner:string,manifest_hash:?string,build_id:?string}>} $ownerDefinition
     * @return array<string, mixed>
     */
    private function section(string $owner, ManagementSection $section, string $identity, int $generation, bool $available, array $ownerDefinition): array
    {
        $readonly = ! $available || ! $this->allowed($owner, $section->writePermission, $generation);
        $fields = $this->fields($owner, $section, $generation);
        $views = $commands = [];
        foreach ($fields as $field) {
            $definition = $field->field;
            $views[] = ['key' => $definition->key, 'type' => $definition->type, 'label' => $definition->names['en'],
                'readonly' => $readonly || ! $definition->writable || ! $this->allowed($owner, $field->writePermission, $generation),
                'sensitive' => $definition->sensitive, 'filterable' => $definition->filterable, 'sortable' => $definition->sortable,
                'lookup' => $definition->referenceModule !== null, 'required' => $definition->required, 'max_length' => $definition->maxLength,
                'choices' => $definition->choices, 'multiple' => $definition->multiple, 'component' => $this->component($ownerDefinition, $field->component)];
        }
        foreach ($section->commands as $command) {
            if ($readonly || ! $this->allowed($owner, $command->safety->operation->permission, $generation)) {
                continue;
            }
            foreach ($command->safety->inputs as $key) {
                if (! isset($fields[$key]) || ! $fields[$key]->field->writable || ! $this->allowed($owner, $fields[$key]->writePermission, $generation)) {
                    continue 2;
                }
            }
            $commands[] = ['key' => $command->key, 'title' => $command->title, 'mode' => $command->mode, 'destructive' => $command->destructive,
                'requirement' => $command->safety->stepUp, 'preview_required' => $command->safety->previewRequired];
        }

        return ['key' => $identity, 'title' => $section->title, 'readonly' => $readonly, 'required' => $section->required,
            'component' => $this->component($ownerDefinition, $section->component), 'fields' => $views, 'commands' => $commands, 'record' => null, 'diagnostic' => $available ? null : 'admin_binding_unavailable'];
    }

    /** @param array{components:array<string,array{key:string,owner:string,manifest_hash:?string,build_id:?string}>} $definition
     * @return array{key:string,owner:string,manifest_hash:?string,build_id:?string}|null
     */
    private function component(array $definition, ?string $key): ?array
    {
        return $key === null ? null : $definition['components'][$key];
    }

    /** @return array<string,ManagementFieldAccess> */
    private function fields(string $owner, ManagementSection $section, int $generation): array
    {
        $fields = [];
        foreach ($section->fields as $field) {
            if ($this->allowed($owner, $field->readPermission, $generation)) {
                $fields[$field->field->key] = $field;
            }
        }

        return $fields;
    }

    /** @param array<string,ManagementFieldAccess> $fields
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

    /** @param array<string,mixed> $result
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
