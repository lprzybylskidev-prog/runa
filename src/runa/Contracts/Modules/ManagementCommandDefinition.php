<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;

final readonly class ManagementCommandDefinition
{
    /** @param string|array{pl:string,en:string} $title */
    public function __construct(
        public string $key, public string|array $title, public ManagementOperation $safety,
        public string $binding, public string $mode = 'independent', public ?string $participant = null,
        public bool $destructive = false,
        public string $intent = 'action',
        public ?string $proofAction = null,
    ) {
        Rules::key($key);
        Rules::text($title);
        Rules::permission($safety->operation->permission ?? throw new \InvalidArgumentException('Missing command permission.'));
        Rules::key($binding);
        if (($intent === 'delete' && ! $destructive) || ! in_array($intent, ['action', 'save', 'create', 'delete'], true)
            || ! in_array($mode, ['independent', 'atomic'], true) || ($mode === 'atomic' && ($participant === null || ! $safety->revisionRequired))) {
            throw new \InvalidArgumentException('Invalid management command mode.');
        }
        if ($participant !== null) {
            Rules::className($participant);
        }
        if ($proofAction !== null) {
            new ApiOperation($proofAction);
        }
        if ($safety->stepUp !== 'none' && $proofAction === null) {
            throw new \InvalidArgumentException('Management proof action must be explicit.');
        }
    }
}
