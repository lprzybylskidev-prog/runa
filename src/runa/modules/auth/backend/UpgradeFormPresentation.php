<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Form action/link presentation correction; no stored data or settings change. */
final class UpgradeFormPresentation implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
