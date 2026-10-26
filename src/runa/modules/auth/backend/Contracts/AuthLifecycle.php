<?php

namespace Runa\Modules\Auth\Contracts;

interface AuthLifecycle
{
    public function create(NewIdentity $input): Identity;

    public function change(string $publicId, int $expectedVersion, LifecycleChange $change): Identity;
}
