<?php

namespace Tests\Fixtures;

use Illuminate\Support\Facades\DB;

/** Exact disposable owner-test objects only. Never used against development data. */
final class CoreModuleStorage
{
    public const TABLES = [
        'permissions' => ['preset_permissions', 'preset_roles', 'presets', 'user_access', 'user_permissions', 'user_roles', 'role_permissions', 'roles', 'permissions'],
        'auth' => ['operator_tokens', 'initialization', 'policy_exceptions', 'policy_versions', 'notice_scan', 'known_browsers', 'notice_frequencies', 'security_notices', 'delivery_preferences', 'preferences', 'profile_mail', 'email_changes', 'secondary_emails', 'avatar_files', 'step_up_proofs', 'password_history', 'login_windows', 'sessions', 'trusted_devices', 'entry_tokens', 'users'],
        'audit' => ['entities', 'events'],
    ];

    public static function removePermissions(): void
    {
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
