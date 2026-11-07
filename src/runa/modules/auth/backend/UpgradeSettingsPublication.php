<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Common source-owned publication preserves current policies and credentials. */
final class UpgradeSettingsPublication implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
