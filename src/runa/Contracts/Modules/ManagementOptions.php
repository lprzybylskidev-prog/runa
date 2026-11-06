<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;
use Runa\ModuleFramework\Management\InputValues as Values;

final readonly class ManagementOptions
{
    /** @param list<array{value:string,label:string}> $items */
    public function __construct(
        public string $field, public array $items, public ?string $next,
    ) {
        Rules::key($field);
        if (count($items) > 100 || ($next !== null && strlen($next) > 2048)) {
            throw new \InvalidArgumentException('Invalid management options.');
        }
        Values::options($items);
    }
}
