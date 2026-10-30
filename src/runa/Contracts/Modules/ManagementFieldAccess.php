<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;

final readonly class ManagementFieldAccess
{
    public function __construct(
        public ManagementField $field, public string $readPermission,
        public ?string $writePermission = null, public ?string $component = null,
    ) {
        Rules::permission($readPermission);
        if ($writePermission !== null) {
            Rules::permission($writePermission);
        }
        Rules::presentation(0, $component);
    }
}
