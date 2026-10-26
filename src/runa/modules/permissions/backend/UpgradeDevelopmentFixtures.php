<?php

namespace Runa\Modules\Permissions;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Only empty provenance storage is installed; production never creates demo records. */
final class UpgradeDevelopmentFixtures implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        if (! $context->connection->getSchemaBuilder()->hasTable('runa_permissions.development_fixtures')) {
            throw new \RuntimeException('Development fixture provenance storage unavailable.');
        }
    }
}
