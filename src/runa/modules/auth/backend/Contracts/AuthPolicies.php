<?php

namespace Runa\Modules\Auth\Contracts;

interface AuthPolicies
{
    /**
     * @return list<array{actor: mixed, operation: string, public_id: string, revision: int, phase: string, values: mixed, changes: mixed, consequences: mixed, created_at: string}>
     */
    public function history(?int $before = null): array;

    /**
     * @return list<array{public_id: string, key: string, value: int, reason: string, actor: string, version: int, policy_revision: int, expires_at: string, revoked_at: string|null}>
     */
    public function exceptions(string $publicId): array;

    /**
     * @param  array<string, mixed>  $patch
     * @return array{revision: int, generation: int, changes: array<string, array{old: mixed, new: mixed}>, consequences: array<string, mixed>}
     */
    public function preview(array $patch, int $revision, int $generation): array;

    /**
     * @param  array<string, mixed>  $patch
     * @return array{operation: string, status: string}
     */
    public function publish(array $patch, int $revision, int $generation): array;

    /**
     * @return array{operation: string, status: string}
     */
    public function restore(int $sourceRevision, int $revision, int $generation): array;

    /**
     * @return array{public_id: string|null, version: int}
     */
    public function exception(string $publicId, string $key, ?int $value, string $reason, string $expiresAt, int $version, int $policyRevision): array;
}
