<?php

namespace Runa\Modules\Admin\Contracts;

/** Guarded API 1.0.0. Every call requires current entry authority and password proof. */
interface AdminAccess
{
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
}
