<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

/** Versioned bounded metadata, with no renderer or executable destinations. */
final readonly class ManagementEntity
{
    /** @var list<ManagementField> */
    public array $fields;

    /** @var list<ManagementOperation> */
    public array $operations;

    /** @param array<string, string> $names
     * @param  array<string, string>  $descriptions
     * @param  array<mixed>  $fields
     * @param  array<mixed>  $operations
     */
    public function __construct(
        public string $key, public string $permission, public array $names, public array $descriptions,
        array $fields, array $operations, public int $schemaVersion = 1,
    ) {
        new ApiOperation($key, $permission);
        ManagementAction::localization($names, $descriptions, $schemaVersion);
        if (! array_is_list($fields) || ! array_is_list($operations) || count($fields) > 64 || count($operations) > 32) {
            throw new InvalidArgumentException('Management entity must be bounded.');
        }
        $seen = [];
        foreach ($fields as $field) {
            if (! $field instanceof ManagementField || isset($seen[$field->key])) {
                throw new InvalidArgumentException('Invalid or duplicate management field.');
            }
            $seen[$field->key] = $field;
        }
        $actions = [];
        foreach ($operations as $operation) {
            if (! $operation instanceof ManagementOperation || isset($actions[$operation->operation->key])) {
                throw new InvalidArgumentException('Invalid or duplicate management operation.');
            }
            $actions[$operation->operation->key] = true;
            foreach ($operation->inputs as $input) {
                if (! isset($seen[$input]) || ! $seen[$input]->writable) {
                    throw new InvalidArgumentException('Management input requires a declared writable field.');
                }
            }
        }
        /** @var list<ManagementField> $fields Validated above. */
        $this->fields = $fields;
        /** @var list<ManagementOperation> $operations Validated above. */
        $this->operations = $operations;
    }
}
