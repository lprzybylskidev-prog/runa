<?php

namespace Runa\Modules\Permissions\Contracts;

use Runa\Contracts\Modules\TrustedPrincipal;

final readonly class RoleDeletionPreview
{
    /** @param list<array{user_public_id: string, revision: int, before: array{roles: list<string>, permissions: list<string>}, after: array{roles: list<string>, permissions: list<string>}, permission_removed: list<string>}> $users
     * @param  list<array{public_id: string, version: int}>  $presets
     */
    public function __construct(public string $publicId, public int $version, public string $fingerprint,
        public array $users, public array $presets)
    {
        new TrustedPrincipal($publicId);
    }
}
