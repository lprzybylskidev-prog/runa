<?php

namespace Runa\Modules\Auth\Contracts;

/** Trusted framework authorization resolver only: non-recursive, no public ingress. */
interface AuthorizationIdentity
{
    public function eligibility(string $publicId): ?Eligibility;
}
