<?php

namespace Runa\Modules\Permissions\Contracts;

/** Reviewed source/state binding; removed source definitions are retained. */
final readonly class DeclarationPreview
{
    /** @param array<string, mixed> $changes */
    public function __construct(public string $module, public string $fingerprint, public array $changes, public int $affectedUsers) {}
}
