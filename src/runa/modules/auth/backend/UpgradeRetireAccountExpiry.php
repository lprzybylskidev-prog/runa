<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;
use Runa\Support\Modules\Settings\ModuleSettings;

final class UpgradeRetireAccountExpiry implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        if ($context->connection->getSchemaBuilder()->hasColumn('runa_auth.users', 'expires_at')) {
            throw new \RuntimeException('Account expiry retirement migration is required.');
        }
        app(ModuleSettings::class)->upgradeDefaults($context, ['notice_account_expiry' => 'notice_account_deactivation', 'expiry_admin_copy' => 'deactivation_admin_copy']);
        foreach (['delivery_preferences', 'security_notices', 'notice_frequencies'] as $table) {
            $query = $context->connection->table('runa_auth.'.$table)->where('category', 'account_expiry');
            if ($table === 'delivery_preferences') {
                $query->where('module', 'runa.auth');
            }
            $query->update(['category' => 'account_deactivation']);
        }
        $context->connection->table('runa_auth.development_fixtures')->where('scenario', 'expired')->update(['scenario' => 'deactivated']);
    }
}
