<?php

namespace Tests\Fixtures;

use Illuminate\Support\Facades\DB;
use Runa\Testing\Database\TestDatabaseGuard;

/** Exact disposable owner-test objects only. Never used against development data. */
final class CoreModuleStorage
{
    public const TABLES = [
        'permissions' => ['development_fixtures', 'preset_permissions', 'preset_roles', 'presets', 'user_access', 'user_permissions', 'user_roles', 'role_permissions', 'roles', 'permissions'],
        'auth' => ['development_fixtures', 'operator_tokens', 'initialization', 'policy_exceptions', 'policy_versions', 'notice_scan', 'known_browsers', 'notice_frequencies', 'security_notices', 'delivery_preferences', 'preferences', 'profile_mail', 'email_changes', 'secondary_emails', 'avatar_files', 'step_up_proofs', 'password_history', 'login_windows', 'sessions', 'trusted_devices', 'entry_tokens', 'users'],
        'audit' => ['entities', 'events'],
    ];

    public static function removeTransitions(string $moduleId): void
    {
        if (! in_array($moduleId, array_map(static fn (string $code): string => 'runa.'.$code, array_keys(self::TABLES)), true)) {
            throw new \InvalidArgumentException('Core fixture cleanup refuses foreign module history.');
        }
        app(TestDatabaseGuard::class)->assertSafe();
        if (! preg_match('/\Aruna_test_test_[1-9][0-9]{0,4}\z/D', DB::connection()->getDatabaseName())) {
            throw new \RuntimeException('Core fixture history cleanup requires a disposable test worker.');
        }
        DB::table('shared.module_transitions')->where('target', $moduleId)->delete();
    }

    public static function removePermissions(): void
    {
        self::removeTransitions('runa.permissions');
        if (DB::selectOne("SELECT 1 FROM pg_namespace WHERE nspname = 'runa_permissions'")) {
            foreach (self::TABLES['permissions'] as $table) {
                DB::statement('DROP TABLE IF EXISTS runa_permissions.'.$table.' RESTRICT');
            }
            DB::statement('DROP SCHEMA runa_permissions RESTRICT');
        }
        $id = DB::table('shared.modules')->where('module_id', 'runa.permissions')->value('id');
        DB::table('shared.module_update_steps')->where('module_id', $id)->delete();
        DB::table('shared.modules')->where('module_id', 'runa.permissions')->delete();
    }
}
