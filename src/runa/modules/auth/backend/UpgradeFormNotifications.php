<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Unified top-right form operation feedback; no stored data or settings change. */
final class UpgradeFormNotifications implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
