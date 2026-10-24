<?php

namespace Runa\Modules\Audit;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Source-only declarations; the reserved preparation participant synchronizes installed Permissions. */
final class UpgradePermissionDeclarations implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
