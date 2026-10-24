<?php

namespace Runa\Modules\Permissions\Contracts;

final readonly class DefinitionPreview
{
    public function __construct(
        public DefinitionInput $input, public string $fingerprint, public ?array $before,
        public array $after, public int $affectedUsers, public array $permissionAdded, public array $permissionRemoved,
    ) {}
}
