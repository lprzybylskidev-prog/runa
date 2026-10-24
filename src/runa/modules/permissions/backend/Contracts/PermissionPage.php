<?php

namespace Runa\Modules\Permissions\Contracts;

/** Bounded authorized projection; foreign internal keys and private Auth data never appear. */
final readonly class PermissionPage
{
    /** @param list<array{code: string, direct: bool, roles: list<string>, roles_truncated: bool, available: bool, effective: bool}> $permissions */
    public function __construct(public string $userPublicId, public bool $eligible, public bool $superAdmin, public array $permissions, public ?string $next) {}
}
