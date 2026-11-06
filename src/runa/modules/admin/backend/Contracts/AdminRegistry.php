<?php

namespace Runa\Modules\Admin\Contracts;

use Runa\Contracts\Modules\ManagementPageProvider;

/** Public bootstrap extension only. Registration grants no authority. */
interface AdminRegistry
{
    public function register(ManagementPageProvider $provider): void;
}
