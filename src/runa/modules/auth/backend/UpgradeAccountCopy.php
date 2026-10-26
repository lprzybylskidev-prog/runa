<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Compatible account copy, alignment and active trust visibility; no schema changes. */
final class UpgradeAccountCopy implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
