<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Runa\Support\Modules\Data\ModuleSchema;
use Runa\Support\Modules\ModuleIdentity;

return new class extends Migration
{
    public function up(): void
    {
        $schema = new ModuleSchema(DB::connection(), new ModuleIdentity('runa.auth'));
        $schema->table('users', function (Blueprint $table): void {
            $table->timestampTz('password_changed_at', 6)->nullable();
            $table->text('totp_secret')->nullable();
            $table->text('totp_pending')->nullable();
            $table->timestampTz('totp_pending_until', 6)->nullable();
            $table->bigInteger('totp_counter')->nullable();
            $table->boolean('email_factor')->default(false);
            $table->jsonb('recovery_hashes')->default('[]');
        });
        DB::statement("ALTER TABLE runa_auth.users ADD CHECK (jsonb_typeof(recovery_hashes) = 'array'), ADD CHECK (totp_counter IS NULL OR totp_counter >= 0), ADD CHECK ((totp_pending IS NULL) = (totp_pending_until IS NULL))");
        // Existing hashes are not evidence of complexity. Start their age at upgrade.
        DB::table('runa_auth.users')->whereNotNull('password')->update(['password_changed_at' => now()]);
        $schema->table('sessions', function (Blueprint $table): void {
            $table->text('factor_method')->nullable();
            $table->timestampTz('factor_verified_at', 6)->nullable();
        });
        DB::statement("ALTER TABLE runa_auth.sessions ADD CHECK ((factor_method IS NULL AND factor_verified_at IS NULL) OR (factor_method IS NOT NULL AND factor_method IN ('totp', 'recovery', 'email') AND factor_verified_at IS NOT NULL))");
        $schema->table('trusted_devices', function (Blueprint $table): void {
            $table->text('factor_method')->nullable();
            $table->timestampTz('created_at', 6)->nullable();
        });
        DB::statement("ALTER TABLE runa_auth.trusted_devices ADD CHECK (factor_method IS NULL OR factor_method IN ('totp', 'recovery', 'email'))");
        DB::statement('ALTER TABLE runa_auth.entry_tokens DROP CONSTRAINT entry_tokens_purpose_check');
        DB::statement("ALTER TABLE runa_auth.entry_tokens ADD CONSTRAINT entry_tokens_purpose_check CHECK (purpose IN ('invitation', 'verification', 'reset', 'email_otp'))");
        $schema->table('entry_tokens', function (Blueprint $table): void {
            $table->text('session_hash')->nullable();
        });
        DB::statement("ALTER TABLE runa_auth.entry_tokens ADD CHECK (purpose <> 'email_otp' OR (session_hash IS NOT NULL AND session_hash ~ '^[0-9a-f]{64}$'))");
        $schema->create('password_history', function (Blueprint $table): void {
            $table->bigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('runa_auth.users')->restrictOnDelete()->restrictOnUpdate();
            $table->text('password_hash');
            $table->timestampTz('created_at', 6);
            $table->index(['user_id', 'id'], 'auth_password_history_user');
        }, exposed: false);
        $schema->create('step_up_proofs', function (Blueprint $table): void {
            $table->bigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('runa_auth.users')->restrictOnDelete()->restrictOnUpdate();
            $table->text('session_hash');
            $table->bigInteger('security_version');
            $table->text('action');
            $table->text('requirement');
            $table->text('factor_method')->nullable();
            $table->timestampTz('verified_at', 6);
            $table->timestampTz('expires_at', 6);
            $table->timestampTz('consumed_at', 6)->nullable();
            $table->index(['user_id', 'session_hash', 'action'], 'auth_step_up_lookup');
        }, exposed: false);
        DB::statement("ALTER TABLE runa_auth.step_up_proofs ADD CHECK (requirement IN ('password', 'second_factor', 'both')), ADD CHECK ((requirement = 'password' AND factor_method IS NULL) OR (requirement <> 'password' AND factor_method IS NOT NULL AND factor_method IN ('totp', 'recovery', 'email'))), ADD CHECK (security_version > 0), ADD CHECK (expires_at > verified_at), ADD CHECK (session_hash ~ '^[0-9a-f]{64}$')");
    }

    public function down(): void
    {
        throw new RuntimeException('Credential recovery requires an explicit forward transition.');
    }
};
