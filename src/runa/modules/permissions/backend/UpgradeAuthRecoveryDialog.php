<?php

namespace Runa\Modules\Permissions;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Compatible Auth recovery modal presentation dependency alignment; no stored data change. */
final class UpgradeAuthRecoveryDialog implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
