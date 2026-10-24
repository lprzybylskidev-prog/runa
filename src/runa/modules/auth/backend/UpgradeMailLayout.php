<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Shared private Auth mail presentation; no stored data or settings change. */
final class UpgradeMailLayout implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
