<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;

final readonly class ManagementOptions
{
    /** @param list<array{value:string,label:string}> $items */
    public function __construct(
        public string $field, public array $items, public ?string $next,
    ) {
        Rules::key($field);
        if (! array_is_list($items) || count($items) > 100 || ($next !== null && strlen($next) > 2048)) {
            throw new \InvalidArgumentException('Invalid management options.');
        }
        foreach ($items as $item) {
            if (! is_array($item) || count($item) !== 2 || ! isset($item['value'], $item['label']) || ! is_string($item['value']) || ! is_string($item['label']) || strlen($item['value']) > 256) {
                throw new \InvalidArgumentException('Invalid management option.');
            }
            Rules::text($item['label']);
        }
    }
}
