<?php

namespace Runa\Contracts\Modules;

/** Optional additive extension on an existing source-owned management declaration. */
interface ManagementEntityProvider extends ManagementProvider
{
    /** @return list<ManagementEntity> */
    public function entities(): array;
}
