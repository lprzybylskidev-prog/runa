<?php

namespace Runa\Modules\Permissions;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Internal platform collaborators changed; no schema or data transformation. */
final class UpgradePlatformComposition implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
