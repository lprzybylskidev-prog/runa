<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Unified account editor presentation correction; no stored data or settings change. */
final class UpgradeAccountEditor implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
