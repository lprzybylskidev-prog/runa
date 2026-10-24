<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Independent account cards and local avatar cropping; no stored data or settings change. */
final class UpgradeAccountCards implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
