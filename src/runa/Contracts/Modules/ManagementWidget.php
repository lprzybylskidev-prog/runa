<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;

final readonly class ManagementWidget
{
    /** @param string|array{pl:string,en:string} $title */
    public function __construct(
        public string $key, public string|array $title, public string $permission, public string $binding,
        public int $order = 0, public ?string $component = null,
    ) {
        Rules::key($key);
        Rules::text($title);
        Rules::permission($permission);
        Rules::key($binding);
        Rules::presentation($order, $component);
    }
}
