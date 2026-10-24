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
        return $this->invoke(__FUNCTION__, [$userPublicId]);
    }

    public function previewAssignments(string $userPublicId, AssignmentSet $requested, string $mode = 'add'): AssignmentPreview
    {
        return $this->invoke(__FUNCTION__, [$userPublicId, $requested, $mode]);
    }

    public function previewCopy(string $sourceUser, string $userPublicId, string $mode = 'add'): AssignmentPreview
    {
        return $this->invoke(__FUNCTION__, [$sourceUser, $userPublicId, $mode]);
    }

    public function previewPreset(string $preset, string $userPublicId, string $mode = 'add'): AssignmentPreview
    {
        return $this->invoke(__FUNCTION__, [$preset, $userPublicId, $mode]);
    }

    public function applyAssignments(AssignmentPreview $reviewed): array
    {
        return $this->invoke(__FUNCTION__, [$reviewed]);
    }

    public function definitions(string $kind, ?string $after = null): array
    {
        return $this->invoke(__FUNCTION__, [$kind, $after]);
    }

    public function definition(string $kind, string $publicId): array
    {
        return $this->invoke(__FUNCTION__, [$kind, $publicId]);
    }

    public function previewDefinition(DefinitionInput $input): DefinitionPreview
    {
        return $this->invoke(__FUNCTION__, [$input]);
    }

    public function saveDefinition(DefinitionPreview $reviewed): array
    {
        return $this->invoke(__FUNCTION__, [$reviewed]);
    }

    public function deleteDefinition(string $kind, string $publicId, int $version): void
    {
        $this->invoke(__FUNCTION__, [$kind, $publicId, $version]);
    }
}
