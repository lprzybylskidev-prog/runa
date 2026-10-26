<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Empty application and user panel presentation correction; no stored data or settings change. */
final class UpgradeUserPanel implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
