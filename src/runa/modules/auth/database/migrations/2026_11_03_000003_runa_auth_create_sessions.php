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
        $connection = DB::connection();
        $schema = new ModuleSchema($connection, new ModuleIdentity('runa.auth'));
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

            $table->text('factor_method')->nullable();
            $table->timestampTz('factor_verified_at', 6)->nullable();
        });
        DB::statement("ALTER TABLE runa_auth.sessions ADD CHECK (session_hash ~ '^[0-9a-f]{64}$'), ADD CHECK (security_version > 0), ADD CHECK (expires_at > authenticated_at AND activity_at >= authenticated_at)");
        DB::statement("ALTER TABLE runa_auth.sessions ADD CHECK ((factor_method IS NULL AND factor_verified_at IS NULL) OR (factor_method IS NOT NULL AND factor_method IN ('totp', 'recovery', 'email') AND factor_verified_at IS NOT NULL))");
    }

    public function down(): void
    {
        throw new RuntimeException('Module table recovery requires an explicit plan.');
    }
};
