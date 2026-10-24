<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Modal avatar cropping with direct manipulation; no stored data or settings change. */
final class UpgradeAvatarModal implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
