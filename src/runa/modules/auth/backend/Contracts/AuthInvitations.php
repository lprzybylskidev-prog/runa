<?php

namespace Runa\Modules\Auth\Contracts;

/** Administrative resend rotates the token; secrets never cross ModuleApis. */
interface AuthInvitations
{
    public function invite(string $publicId, int $expectedVersion): bool;

    public function invalidate(string $publicId, int $expectedVersion): bool;
}
