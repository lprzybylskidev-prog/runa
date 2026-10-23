<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** No default user/credential/admin flag. Preparation already records the Audit boundary. */
final class InitializeAuth implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        if (! $context->connection->getSchemaBuilder()->hasTable('runa_auth.users')) {
            throw new \RuntimeException('Auth identity storage unavailable.');
        }
    }
}
