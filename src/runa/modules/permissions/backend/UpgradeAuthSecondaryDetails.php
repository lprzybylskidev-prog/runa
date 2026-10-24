<?php

namespace Runa\Modules\Permissions;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Compatible Auth secondary-address details presentation dependency alignment; no stored data change. */
final class UpgradeAuthSecondaryDetails implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
