<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthPolicies;
use Runa\Support\Modules\Api\GuardedApi;

final class PoliciesProxy extends GuardedApi implements AuthPolicies
{
    public function history(?int $before = null): array
    {
        return $this->invoke(__FUNCTION__, [$before]);
    }

    public function exceptions(string $publicId): array
    {
        return $this->invoke(__FUNCTION__, [$publicId]);
    }

    public function preview(array $patch, int $revision, int $generation): array
    {
        return $this->invoke(__FUNCTION__, [$patch, $revision, $generation]);
    }

    public function publish(array $patch, int $revision, int $generation): array
    {
        return $this->invoke(__FUNCTION__, [$patch, $revision, $generation]);
    }

    public function restore(int $sourceRevision, int $revision, int $generation): array
    {
        return $this->invoke(__FUNCTION__, [$sourceRevision, $revision, $generation]);
    }

    public function exception(string $publicId, string $key, ?int $value, string $reason, string $expiresAt, int $version, int $policyRevision): array
    {
        return $this->invoke(__FUNCTION__, [$publicId, $key, $value, $reason, $expiresAt, $version, $policyRevision]);
    }
}
