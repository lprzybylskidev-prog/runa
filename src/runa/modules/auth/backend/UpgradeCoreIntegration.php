<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Source-only integration edge; reserved preparation owns declaration/policy history. */
final class UpgradeCoreIntegration implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
