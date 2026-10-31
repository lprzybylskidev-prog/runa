<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

final class UpgradeSectionActivity implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        if (! $context->connection->getSchemaBuilder()->hasColumn('runa_auth.step_up_proofs', 'idle_expires_at')) {
            throw new \RuntimeException('Auth section activity storage unavailable.');
        }
    }
}
