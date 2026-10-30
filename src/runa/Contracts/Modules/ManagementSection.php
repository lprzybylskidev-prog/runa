<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;

final readonly class ManagementSection
{
    /** @param list<ManagementFieldAccess> $fields
     * @param list<ManagementCommandDefinition> $commands */
    public function __construct(
        public string $key, public string $title, public string $readPermission,
        public ?string $writePermission, public string $binding, public array $fields,
        public array $commands = [], public int $order = 0, public bool $required = true,
        public ?string $component = null, public int $schemaVersion = 1,
    ) {
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
    }
}
