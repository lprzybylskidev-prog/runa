<?php

namespace Runa\Modules\Permissions;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Preparation participation synchronizes the shared table export permission. */
final class UpgradeTableExports implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
