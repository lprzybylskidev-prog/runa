<?php

namespace Runa\Modules\Auth\Contracts;

interface AuthPolicies
{
    public function history(?int $before = null): array;

    public function exceptions(string $publicId): array;

    public function preview(array $patch, int $revision, int $generation): array;

    public function publish(array $patch, int $revision, int $generation): array;

    public function restore(int $sourceRevision, int $revision, int $generation): array;

    public function exception(string $publicId, string $key, ?int $value, string $reason, string $expiresAt, int $version, int $policyRevision): array;
}
