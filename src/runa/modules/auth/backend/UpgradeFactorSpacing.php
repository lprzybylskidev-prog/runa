<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Compatible factor-row spacing correction; no schema changes. */
final class UpgradeFactorSpacing implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
