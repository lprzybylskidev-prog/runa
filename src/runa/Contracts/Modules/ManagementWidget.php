<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;

final readonly class ManagementWidget
{
    public function __construct(
        public string $key, public string $title, public string $permission, public string $binding,
        public int $order = 0, public ?string $component = null,
    ) {
        Rules::key($key);
        Rules::text($title);
        Rules::permission($permission);
        Rules::key($binding);
        Rules::presentation($order, $component);
    }
}
