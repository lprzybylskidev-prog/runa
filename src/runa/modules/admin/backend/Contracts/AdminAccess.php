<?php

namespace Runa\Modules\Admin\Contracts;

/** Guarded API 1.0.0. Protected calls require current entry authority and password proof; entry is a minimal advertisement. */
interface AdminAccess
{
    /** @return array{available: bool} Proof-free advertisement; never admits protected reads. */
    public function entry(): array;

    /** @return array<string, mixed> */
    public function bootstrap(): array;

    /** @return array<string, mixed> */
    public function page(string $owner, string $page): array;

    /** @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function read(string $owner, string $page, string $section, array $query): array;

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function preview(string $owner, string $page, array $input): array;

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function save(string $owner, string $page, array $input): array;

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function command(string $owner, string $page, string $section, array $input): array;

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function previewCommand(string $owner, string $page, string $section, array $input): array;
}
