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
        $schema->create('sessions', function (Blueprint $table): void {
            $table->bigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('runa_auth.users')->restrictOnDelete()->restrictOnUpdate();
            $table->text('session_hash')->unique('auth_session_hash');
            $table->bigInteger('security_version');
            $table->boolean('remembered');
            $table->timestampTz('authenticated_at', 6);
            $table->timestampTz('activity_at', 6);
            $table->timestampTz('expires_at', 6);
            $table->timestampTz('revoked_at', 6)->nullable();
            $table->index(['user_id', 'authenticated_at', 'id'], 'auth_sessions_oldest');
            $table->index('expires_at', 'auth_sessions_expiry');
            $table->index(['user_id', 'public_id'], 'auth_sessions_list');
        });
        DB::statement("ALTER TABLE runa_auth.sessions ADD CHECK (session_hash ~ '^[0-9a-f]{64}$'), ADD CHECK (security_version > 0), ADD CHECK (expires_at > authenticated_at AND activity_at >= authenticated_at)");
        // Trust issuance/verification follows in point 7; store only revocable operational proofs here.
        $schema->create('trusted_devices', function (Blueprint $table): void {
            $table->bigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('runa_auth.users')->restrictOnDelete()->restrictOnUpdate();
            $table->text('token_hash')->unique('auth_trust_hash');
            $table->bigInteger('security_version');
            $table->timestampTz('expires_at', 6);
            $table->timestampTz('revoked_at', 6)->nullable();
            $table->index('user_id', 'auth_trust_user');
            $table->index('expires_at', 'auth_trust_expiry');
        });
        DB::statement("ALTER TABLE runa_auth.trusted_devices ADD CHECK (token_hash ~ '^[0-9a-f]{64}$'), ADD CHECK (security_version > 0)");
        $schema->create('login_windows', function (Blueprint $table): void {
            $table->text('fingerprint')->unique('auth_abuse_fingerprint');
            $table->integer('attempts');
            $table->timestampTz('expires_at', 6);
            $table->timestampTz('locked_until', 6)->nullable();
            $table->index('expires_at', 'auth_abuse_expiry');
        }, exposed: false);
        DB::statement("ALTER TABLE runa_auth.login_windows ADD CHECK (fingerprint ~ '^[0-9a-f]{64}$'), ADD CHECK (attempts > 0)");
    }

    public function down(): void
    {
        throw new RuntimeException('Auth session recovery requires an explicit forward transition.');
    }
};
