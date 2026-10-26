<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Source-only lifecycle guard repair; preserves all identities and policy state. */
final class UpgradeAuthorityParticipation implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
