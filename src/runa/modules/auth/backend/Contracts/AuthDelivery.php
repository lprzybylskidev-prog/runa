<?php

namespace Runa\Modules\Auth\Contracts;

interface AuthDelivery
{
    /** Authorized sender resolves only current safe email recipients; never credentials.
     *
     * @return list<string>
     */
    public function recipients(string $publicId, string $module, string $category): array;
}
