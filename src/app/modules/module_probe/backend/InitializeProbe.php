<?php

namespace App\Modules\ModuleProbe;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

final class InitializeProbe implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        // Explicit stateless initialization: only the framework's completion evidence changes.
    }
}
