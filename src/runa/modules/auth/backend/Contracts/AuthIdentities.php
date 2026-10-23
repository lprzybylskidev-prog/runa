<?php

namespace Runa\Modules\Auth\Contracts;

interface AuthIdentities
{
    public function identity(string $publicId): ?Identity;

    public function eligibility(string $publicId): ?Eligibility;
}
