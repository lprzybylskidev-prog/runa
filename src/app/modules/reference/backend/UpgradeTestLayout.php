<?php

namespace App\Modules\Reference;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Test metadata relocation requires no schema or data transformation. */
final class UpgradeTestLayout implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
