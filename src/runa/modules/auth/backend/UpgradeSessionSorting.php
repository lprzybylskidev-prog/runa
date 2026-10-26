<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Chronological account table sorting and concise session actions; no stored data changes. */
final class UpgradeSessionSorting implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
