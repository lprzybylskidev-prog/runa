<?php

namespace Runa\Integration\Core;

use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Modules\Auth\Contracts\AuthorizationIdentity;
use Runa\Modules\Auth\Contracts\Eligibility;
use Runa\Modules\Auth\Queries\LookupIdentity;

/** Sole trusted bridge; Permissions never imports Auth persistence or private queries. */
final readonly class AuthAuthorizationIdentity implements AuthorizationIdentity
{
    public function __construct(private ModuleAvailability $availability, private ModuleRegistry $registry) {}

    public function eligibility(string $publicId): ?Eligibility
    {
        $generation = $this->registry->snapshot()->generation;
        if (! $this->availability->statuses('runa.auth', 'authorization.identity')['runa.auth']->available) {
            return null;
        }
        $result = app(LookupIdentity::class)->eligibility($publicId);

        return $this->registry->snapshot()->generation === $generation ? $result : null;
    }
}
