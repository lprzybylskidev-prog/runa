<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Compatible policy read grouping changes no schema, stored settings or credentials. */
final class UpgradeReadScopes implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
