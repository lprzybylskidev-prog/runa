<?php

namespace Runa\Modules\Permissions;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Platform compatibility and image projection require no schema or data transformation. */
final class UpgradePlatformDelivery implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
