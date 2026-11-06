<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;

final readonly class ManagementOptionsRead
{
    /** @param array<string, scalar|null> $filters */
    public function __construct(public string $field, public ?string $publicId = null,
        public ?string $cursor = null, public int $limit = 25, public array $filters = [])
    {
        Rules::key($field);
        new ManagementRead($publicId, $cursor, $limit, $filters);
    }
}
