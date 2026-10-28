<?php

namespace Runa\Modules\Permissions;

use Runa\Modules\Permissions\Contracts\AssignmentPreview;
use Runa\Modules\Permissions\Contracts\AssignmentSet;
use Runa\Modules\Permissions\Contracts\DefinitionInput;
use Runa\Modules\Permissions\Contracts\DefinitionPreview;
use Runa\Modules\Permissions\Contracts\PermissionsAccess;
use Runa\Support\Modules\Api\GuardedApi;

final class AccessProxy extends GuardedApi implements PermissionsAccess
{
    public function assignments(string $userPublicId): array
    {
        /** @var array{user_public_id: string, identity_version: int, eligible: bool, security_version: int, revision: int, super_admin: bool, assigned: array{roles: list<string>, permissions: list<string>}, definitions: array{roles: list<array{public_id: string, code: string, version: int, module: string|null, permissions?: list<array{public_id: string, code: string, version: int, module_id: string|null}>}>, permissions: list<array{public_id: string, code: string, version: int, module: string|null, permissions?: list<array{public_id: string, code: string, version: int, module_id: string|null}>}>}} $result */
        $result = $this->invoke(__FUNCTION__, [$userPublicId]);

        return $result;
    }

    public function previewAssignments(string $userPublicId, AssignmentSet $requested, string $mode = 'add'): AssignmentPreview
    {
        /** @var AssignmentPreview $result */
        $result = $this->invoke(__FUNCTION__, [$userPublicId, $requested, $mode]);

        return $result;
    }

    public function previewCopy(string $sourceUser, string $userPublicId, string $mode = 'add'): AssignmentPreview
    {
        /** @var AssignmentPreview $result */
        $result = $this->invoke(__FUNCTION__, [$sourceUser, $userPublicId, $mode]);

        return $result;
    }

    public function previewPreset(string $preset, string $userPublicId, string $mode = 'add'): AssignmentPreview
    {
        /** @var AssignmentPreview $result */
        $result = $this->invoke(__FUNCTION__, [$preset, $userPublicId, $mode]);

        return $result;
    }

    public function applyAssignments(AssignmentPreview $reviewed): array
    {
        /** @var array{user_public_id: string, identity_version: int, eligible: bool, security_version: int, revision: int, super_admin: bool, assigned: array{roles: list<string>, permissions: list<string>}, definitions: array{roles: list<array{public_id: string, code: string, version: int, module: string|null, permissions?: list<array{public_id: string, code: string, version: int, module_id: string|null}>}>, permissions: list<array{public_id: string, code: string, version: int, module: string|null, permissions?: list<array{public_id: string, code: string, version: int, module_id: string|null}>}>}} $result */
        $result = $this->invoke(__FUNCTION__, [$reviewed]);

        return $result;
    }

    public function definitions(string $kind, ?string $after = null): array
    {
        /** @var array{items: list<array{public_id: string, code: string, name_pl: string, name_en: string, version: int}>, next: string|null} $result */
        $result = $this->invoke(__FUNCTION__, [$kind, $after]);

        return $result;
    }

    public function definition(string $kind, string $publicId): array
    {
        /** @var array{public_id: string, version: int, dictionary: array<string, string>, module: string|null, system: bool, assigned: array{roles: list<string>, permissions: list<string>}} $result */
        $result = $this->invoke(__FUNCTION__, [$kind, $publicId]);

        return $result;
    }

    public function previewDefinition(DefinitionInput $input): DefinitionPreview
    {
        /** @var DefinitionPreview $result */
        $result = $this->invoke(__FUNCTION__, [$input]);

        return $result;
    }

    public function saveDefinition(DefinitionPreview $reviewed): array
    {
        /** @var array{public_id: string, version: int, dictionary: array<string, string>, module: string|null, system: bool, assigned: array{roles: list<string>, permissions: list<string>}} $result */
        $result = $this->invoke(__FUNCTION__, [$reviewed]);

        return $result;
    }

    public function deleteDefinition(string $kind, string $publicId, int $version): void
    {
        $this->invoke(__FUNCTION__, [$kind, $publicId, $version]);
    }
}
