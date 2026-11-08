<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;
use Runa\Modules\Auth\Actions\PolicyHistory;

/** No default user/credential/admin flag. Preparation already records the Audit boundary. */
final class InitializeAuth implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        foreach (['users', 'entry_tokens', 'sessions', 'trusted_devices', 'login_windows', 'password_history', 'step_up_proofs', 'secondary_emails', 'email_changes', 'profile_mail', 'preferences', 'delivery_preferences', 'security_notices', 'notice_frequencies', 'known_browsers', 'policy_versions', 'policy_exceptions', 'operator_tokens', 'initialization', 'notice_scan', 'development_fixtures'] as $table) {
            if (! $context->connection->getSchemaBuilder()->hasTable('runa_auth.'.$table)) {
                throw new \RuntimeException('Auth initialization storage unavailable.');
            }
        }
        if (! $context->connection->getSchemaBuilder()->hasColumn('runa_auth.step_up_proofs', 'idle_expires_at')) {
            throw new \RuntimeException('Auth section activity storage unavailable.');
        }
        foreach (['notice_scan' => ['last_user_id' => 0], 'initialization' => ['subject' => null, 'completed_at' => null]] as $table => $values) {
            if (! $context->connection->table('runa_auth.'.$table)->exists()) {
                $context->connection->table('runa_auth.'.$table)->insert($values);
            }
        }
        app(PolicyHistory::class)->baseline($context->connection);
    }
}
