<?php

namespace Runa\Modules\Permissions\Contracts;

/** Reviewed source/state binding; removed source definitions are retained.
 * @phpstan-type Change array{before: array<string, string>|null, after: array<string, string>, version: int|null, members_before: list<string>, members_after: list<string>}
 */
final readonly class DeclarationPreview
{
    /** @param array<'permissions'|'roles', array<string, Change>> $changes */
    public function __construct(public string $module, public string $fingerprint, public array $changes, public int $affectedUsers) {}
}
