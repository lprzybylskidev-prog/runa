<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;

final readonly class ManagementSection
{
    /** @param list<ManagementFieldAccess> $fields
     * @param  list<ManagementCommandDefinition>  $commands
     * @param  string|array{pl:string,en:string}  $title
     */
    public function __construct(
        public string $key, public string|array $title, public string $readPermission,
        public ?string $writePermission, public string $binding, public array $fields,
        public array $commands = [], public int $order = 0, public bool $required = true,
        public ?string $component = null, public int $schemaVersion = 1, public string $view = 'record',
    ) {
        if (! in_array($view, ['record', 'table'], true)) {
            throw new \InvalidArgumentException('Invalid management section view.');
        }
        Rules::key($key);
        Rules::text($title);
        Rules::permission($readPermission);
        if ($writePermission !== null) {
            Rules::permission($writePermission);
        }
        Rules::key($binding);
        Rules::collection($fields, ManagementFieldAccess::class, 64, 'field');
        Rules::collection($commands, ManagementCommandDefinition::class, 32);
        Rules::presentation($order, $component, $schemaVersion);
        $writable = [];
        foreach ($fields as $field) {
            if ($field->field->writable && $field->writePermission !== null) {
                $writable[] = $field->field->key;
            }
        }
        foreach ($commands as $command) {
            if (array_diff($command->safety->inputs, $writable) !== []) {
                throw new \InvalidArgumentException('Command inputs require writable declared fields.');
            }
        }
        if (count(array_filter($commands, fn ($command): bool => $command->intent === 'save')) > 1
            || count(array_filter($commands, fn ($command): bool => $command->intent === 'create')) > 1) {
            throw new \InvalidArgumentException('Ambiguous management form commands.');
        }
    }
}
