<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Compatible frontend source contracts require no schema or data transformation. */
final class UpgradeFrontendContracts implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
