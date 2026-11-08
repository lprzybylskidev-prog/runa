<?php

namespace App\Modules\Reference\Contracts;

/** Guarded application example API 1.0.0; no models or caller authority cross it. */
interface ReferenceTools
{
    /** @return array{items:list<array{public_id:string,revision:string,code?:string}>} */
    public function samples(): array;

    /** @return array<string,mixed> */
    public function editSample(string $publicId, string $revision, string $code): array;

    /** @return array{revision:int,review_id:string,settings:array<string,array{names:array<string,string>,descriptions:array<string,string>,value?:mixed,set?:bool}>} */
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

    /** @return array{items:list<array{public_id:string,occurred_at:string,action:string,outcome:string}>,next:?string} */
    public function history(?string $sample = null, ?string $before = null): array;

    /** @return array<string,mixed> */
    public function dispatch(): array;

    /** @return array{items:list<array{work_id:string,operation:string,status:string,progress:string,attempts:int,cancellation_requested:bool} >} */
    public function work(): array;
}
