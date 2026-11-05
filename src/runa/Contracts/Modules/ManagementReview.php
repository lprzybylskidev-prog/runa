<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;

final readonly class ManagementReview
{
    /** @param array<string, mixed> $changes */
    public function __construct(
        public string $fingerprint, public array $changes, public string $requirement, public bool $everyAction,
    ) {
        Rules::revision($fingerprint);
        Rules::json($changes);
        if (! in_array($requirement, ['none', 'password'], true)) {
            throw new \InvalidArgumentException('Invalid review requirement.');
        }
    }
}
