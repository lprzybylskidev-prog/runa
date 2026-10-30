<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;

final readonly class ManagementCommandDefinition
{
    public function __construct(
        public string $key, public string $title, public ManagementOperation $safety,
        public string $binding, public string $mode = 'independent', public ?string $participant = null,
        public bool $destructive = false,
    ) {
        Rules::key($key);
        Rules::text($title);
        Rules::permission($safety->operation->permission ?? throw new \InvalidArgumentException('Missing command permission.'));
        Rules::key($binding);
        if (! in_array($mode, ['independent', 'atomic'], true) || ($mode === 'atomic' && ($participant === null || ! $safety->revisionRequired))) {
            throw new \InvalidArgumentException('Invalid management command mode.');
        }
        if ($participant !== null) {
            Rules::className($participant);
        }
    }
}
