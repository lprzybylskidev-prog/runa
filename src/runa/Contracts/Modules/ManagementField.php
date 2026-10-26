<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

final readonly class ManagementField
{
    /** Reference metadata describes a public UUIDv7 lookup, never a model/table/path.
     * @param  array<mixed>  $choices
     * @param  array<string, string>  $names
     * @param  array<string, string>  $descriptions
     */
    public function __construct(
        public string $key, public string $type, public array $names, public array $descriptions,
        public bool $filterable = false, public bool $sortable = false, public bool $writable = false,
        public bool $sensitive = false, public ?string $referenceModule = null, public ?string $referenceOperation = null,
        public bool $required = false, public ?int $maxLength = null, public array $choices = [], public bool $multiple = false,
    ) {
        new ApiOperation($key);
        ManagementAction::localization($names, $descriptions, 1);
        if (! in_array($type, ['text', 'boolean', 'integer', 'datetime', 'uuid', 'dictionary'], true)
            || ($sensitive && ($filterable || $sortable))
            || (($referenceModule === null) !== ($referenceOperation === null))) {
            throw new InvalidArgumentException('Invalid management field.');
        }
        if (($required && ! $writable) || ($maxLength !== null && ($type !== 'text' || $maxLength < 1 || $maxLength > 65536))
            || ! array_is_list($choices) || count($choices) > 64 || ($choices !== [] && ! in_array($type, ['dictionary', 'text'], true))
            || ($multiple && $type !== 'uuid')) {
            throw new InvalidArgumentException('Invalid management input controls.');
        }
        foreach ($choices as $choice) {
            if (! is_string($choice) || preg_match('/\A[a-z][a-z0-9_.:-]{0,127}\z/D', $choice) !== 1) {
                throw new InvalidArgumentException('Invalid management choice.');
            }
        }
        if (count(array_unique($choices)) !== count($choices)) {
            throw new InvalidArgumentException('Duplicate management choice.');
        }
        if ($referenceModule !== null) {
            if ($type !== 'uuid' || preg_match('/\A(?:runa|app)\.[a-z][a-z0-9_]{0,47}\z/D', $referenceModule) !== 1) {
                throw new InvalidArgumentException('Invalid management reference.');
            }
            new ApiOperation($referenceOperation ?? throw new InvalidArgumentException('Missing reference operation.'));
        }
    }
}
