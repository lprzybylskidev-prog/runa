<?php

namespace Runa\Modules\Permissions\Contracts;

/** Authorized ordinary access management; all mutations enforce fresh both-factor proof. */
interface PermissionsAccess
{
    public function assignments(string $userPublicId): array;

    public function previewAssignments(string $userPublicId, AssignmentSet $requested, string $mode = 'add'): AssignmentPreview;

    public function previewCopy(string $sourceUser, string $userPublicId, string $mode = 'add'): AssignmentPreview;

    public function previewPreset(string $preset, string $userPublicId, string $mode = 'add'): AssignmentPreview;

    public function applyAssignments(AssignmentPreview $reviewed): array;

    public function definitions(string $kind, ?string $after = null): array;

    public function definition(string $kind, string $publicId): array;

    public function previewDefinition(DefinitionInput $input): DefinitionPreview;

    public function saveDefinition(DefinitionPreview $reviewed): array;

    public function deleteDefinition(string $kind, string $publicId, int $version): void;
}
