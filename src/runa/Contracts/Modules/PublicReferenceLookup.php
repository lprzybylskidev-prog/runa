<?php

namespace Runa\Contracts\Modules;

/** Implement through the target module's public API, never a foreign model/query. */
interface PublicReferenceLookup
{
    public function exists(string $publicId): bool;
}
