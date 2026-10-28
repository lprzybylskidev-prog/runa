<?php

namespace Runa\Modules\Permissions\Contracts;

final readonly class DefinitionPreview
{
    /**
     * @param  array{public_id: string, version: int, dictionary: array<string, string>, module: string|null, system: bool, assigned: array{roles: list<string>, permissions: list<string>}}|null  $before
     * @param  array{dictionary: array<string, string>, assigned: array{roles: list<string>, permissions: list<string>}}  $after
     * @param  list<string>  $permissionAdded
     * @param  list<string>  $permissionRemoved
     */
    public function __construct(
        public DefinitionInput $input, public string $fingerprint, public ?array $before,
        public array $after, public int $affectedUsers, public array $permissionAdded, public array $permissionRemoved,
    ) {}
}
