<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;
use Runa\Support\Modules\Settings\ModuleSettings;

final class UpgradeProfiles implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        foreach (['secondary_emails', 'email_changes', 'profile_mail'] as $table) {
            if (! $context->connection->getSchemaBuilder()->hasTable('runa_auth.'.$table)) {
                throw new \RuntimeException('Auth profile storage unavailable.');
            }
        }
        app(ModuleSettings::class)->upgradeDefaults($context);
    }
}
