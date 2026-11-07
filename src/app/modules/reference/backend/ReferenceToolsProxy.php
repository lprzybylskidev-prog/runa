<?php

namespace App\Modules\Reference;

use App\Modules\Reference\Contracts\ReferenceTools;
use Runa\Support\Modules\Api\GuardedApi;

final class ReferenceToolsProxy extends GuardedApi implements ReferenceTools
{
    public function samples(): array
    {
        $result = $this->invoke(__FUNCTION__, []);

        return is_array($result) ? $result : throw new \LogicException('Invalid reference response.');
    }

    public function editSample(string $publicId, string $revision, string $code): array
    {
        $result = $this->invoke(__FUNCTION__, [$publicId, $revision, $code]);

        return is_array($result) ? $result : throw new \LogicException('Invalid reference response.');
    }

    public function settings(): array
    {
        $result = $this->invoke(__FUNCTION__, []);

        return is_array($result) ? $result : throw new \LogicException('Invalid reference response.');
    }

    public function reviewSettings(#[\SensitiveParameter] array $patch, int $revision, string $reviewId): array
    {
        $result = $this->invoke(__FUNCTION__, [$patch, $revision, $reviewId]);

        return is_array($result) ? $result : throw new \LogicException('Invalid reference response.');
    }

    public function publishSettings(#[\SensitiveParameter] array $patch, int $revision, string $reviewId, string $review): array
    {
        $result = $this->invoke(__FUNCTION__, [$patch, $revision, $reviewId, $review]);

        return is_array($result) ? $result : throw new \LogicException('Invalid reference response.');
    }

    public function snapshots(?string $publicId = null): array
    {
        $result = $this->invoke(__FUNCTION__, [$publicId]);

        return is_array($result) ? $result : throw new \LogicException('Invalid reference response.');
    }

    public function captureSnapshot(string $name, int $revision, string $reviewId): array
    {
        $result = $this->invoke(__FUNCTION__, [$name, $revision, $reviewId]);

        return is_array($result) ? $result : throw new \LogicException('Invalid reference response.');
    }

    public function deleteSnapshot(string $publicId, int $revision, string $reviewId): array
    {
        $result = $this->invoke(__FUNCTION__, [$publicId, $revision, $reviewId]);

        return is_array($result) ? $result : throw new \LogicException('Invalid reference response.');
    }

    public function history(?string $sample = null, ?string $before = null): array
    {
        $result = $this->invoke(__FUNCTION__, [$sample, $before]);

        return is_array($result) ? $result : throw new \LogicException('Invalid reference response.');
    }

    public function dispatch(): array
    {
        $result = $this->invoke(__FUNCTION__, []);

        return is_array($result) ? $result : throw new \LogicException('Invalid reference response.');
    }

    public function work(): array
    {
        $result = $this->invoke(__FUNCTION__, []);

        return is_array($result) ? $result : throw new \LogicException('Invalid reference response.');
    }
}
