<?php

namespace Runa\Modules\Permissions\Contracts;

/** Authorized ordinary access management; all mutations enforce fresh both-factor proof. */
interface PermissionsAccess
{
    /** @return array{user_public_id: string, identity_version: int, eligible: bool, security_version: int, revision: int, super_admin: bool, assigned: array{roles: list<string>, permissions: list<string>}, definitions: array{roles: list<array{public_id: string, code: string, version: int, module: string|null, permissions?: list<array{public_id: string, code: string, version: int, module_id: string|null}>}>, permissions: list<array{public_id: string, code: string, version: int, module: string|null, permissions?: list<array{public_id: string, code: string, version: int, module_id: string|null}>}>}} */
    public function assignments(string $userPublicId): array;

    public function previewAssignments(string $userPublicId, AssignmentSet $requested, string $mode = 'add'): AssignmentPreview;

    public function previewCopy(string $sourceUser, string $userPublicId, string $mode = 'add'): AssignmentPreview;

    public function previewPreset(string $preset, string $userPublicId, string $mode = 'add'): AssignmentPreview;

    /** @return array{user_public_id: string, identity_version: int, eligible: bool, security_version: int, revision: int, super_admin: bool, assigned: array{roles: list<string>, permissions: list<string>}, definitions: array{roles: list<array{public_id: string, code: string, version: int, module: string|null, permissions?: list<array{public_id: string, code: string, version: int, module_id: string|null}>}>, permissions: list<array{public_id: string, code: string, version: int, module: string|null, permissions?: list<array{public_id: string, code: string, version: int, module_id: string|null}>}>}} */
    public function applyAssignments(AssignmentPreview $reviewed): array;

    /** @return array{items: list<array{public_id: string, code: string, name_pl: string, name_en: string, version: int}>, next: string|null} */
    public function definitions(string $kind, ?string $after = null): array;

    /** @return array{public_id: string, version: int, dictionary: array<string, string>, module: string|null, system: bool, assigned: array{roles: list<string>, permissions: list<string>}} */
    public function definition(string $kind, string $publicId): array;

    public function previewDefinition(DefinitionInput $input): DefinitionPreview;

    /** @return array{public_id: string, version: int, dictionary: array<string, string>, module: string|null, system: bool, assigned: array{roles: list<string>, permissions: list<string>}} */
    public function saveDefinition(DefinitionPreview $reviewed): array;

    public function deleteDefinition(string $kind, string $publicId, int $version): void;
}
