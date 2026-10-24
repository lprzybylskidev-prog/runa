<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Welcome header presentation correction; no stored data or settings change. */
final class UpgradeWelcomeHeader implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
