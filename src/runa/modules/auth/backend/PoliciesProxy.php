<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthPolicies;
use Runa\Support\Modules\Api\GuardedApi;

final class PoliciesProxy extends GuardedApi implements AuthPolicies
{
    public function history(?int $before = null): array
    {
        /** @var list<array{actor: mixed, operation: string, public_id: string, revision: int, phase: string, values: mixed, changes: mixed, consequences: mixed, created_at: string}> $result */
        $result = $this->invoke(__FUNCTION__, [$before]);

        return $result;
    }

    public function exceptions(string $publicId): array
    {
        /** @var list<array{public_id: string, key: string, value: int, reason: string, actor: string, version: int, policy_revision: int, expires_at: string, revoked_at: string|null}> $result */
        $result = $this->invoke(__FUNCTION__, [$publicId]);

        return $result;
    }

    public function preview(array $patch, int $revision, int $generation): array
    {
        /** @var array{revision: int, generation: int, changes: array<string, array{old: mixed, new: mixed}>, consequences: array<string, mixed>} $result */
        $result = $this->invoke(__FUNCTION__, [$patch, $revision, $generation]);

        return $result;
    }

    public function publish(array $patch, int $revision, int $generation): array
    {
        /** @var array{operation: string, status: string} $result */
        $result = $this->invoke(__FUNCTION__, [$patch, $revision, $generation]);

        return $result;
    }

    public function restore(int $sourceRevision, int $revision, int $generation): array
    {
        /** @var array{operation: string, status: string} $result */
        $result = $this->invoke(__FUNCTION__, [$sourceRevision, $revision, $generation]);

        return $result;
    }

    public function exception(string $publicId, string $key, ?int $value, string $reason, string $expiresAt, int $version, int $policyRevision): array
    {
        /** @var array{public_id: string|null, version: int} $result */
        $result = $this->invoke(__FUNCTION__, [$publicId, $key, $value, $reason, $expiresAt, $version, $policyRevision]);

        return $result;
    }
}
