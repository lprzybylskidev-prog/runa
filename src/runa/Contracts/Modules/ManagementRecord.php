<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;

final readonly class ManagementRecord
{
    /** @param array<string, mixed> $values */
    public function __construct(
        public ?string $publicId, public string $revision, public array $values,
    ) {
        Rules::publicId($publicId);
        Rules::revision($revision);
        Rules::json($values);
    }
}
