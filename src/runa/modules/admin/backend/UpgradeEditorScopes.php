<?php

namespace Runa\Modules\Admin;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Compatible frontend editor lifetimes require no schema or data transformation. */
final class UpgradeEditorScopes implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
