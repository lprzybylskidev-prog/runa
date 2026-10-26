<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Grouped preference cards and explicit modal delivery/reset; no stored data changes. */
final class UpgradePreferencesPresentation implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
