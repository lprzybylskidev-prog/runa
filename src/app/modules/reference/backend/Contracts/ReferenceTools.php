<?php

namespace App\Modules\Reference\Contracts;

/** Guarded application example API 1.0.0; no models or caller authority cross it. */
interface ReferenceTools
{
    /** @return array<string,mixed> */
    public function samples(): array;

    /** @return array<string,mixed> */
    public function editSample(string $publicId, string $revision, string $code): array;

    /** @return array<string,mixed> */
    public function settings(): array;

    /** @param array<string,mixed> $patch
     * @return array<string,mixed> */
    public function reviewSettings(#[\SensitiveParameter] array $patch, int $revision, string $reviewId): array;

    /** @param array<string,mixed> $patch
     * @return array<string,mixed> */
    public function publishSettings(#[\SensitiveParameter] array $patch, int $revision, string $reviewId, string $review): array;

    /** @return array<string,mixed> */
    public function snapshots(?string $publicId = null): array;

    /** @return array<string,mixed> */
    public function captureSnapshot(string $name, int $revision, string $reviewId): array;

    /** @return array<string,mixed> */
    public function deleteSnapshot(string $publicId, int $revision, string $reviewId): array;

    /** @return array<string,mixed> */
    public function history(?string $sample = null, ?string $before = null): array;

    /** @return array<string,mixed> */
    public function dispatch(): array;

    /** @return array<string,mixed> */
    public function work(): array;
}
