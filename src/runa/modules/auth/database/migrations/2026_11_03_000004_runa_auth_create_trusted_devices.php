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
        $schema->create('trusted_devices', function (Blueprint $table): void {
            $table->bigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('runa_auth.users')->restrictOnDelete()->restrictOnUpdate();
            $table->text('token_hash')->unique('auth_trust_hash');
            $table->bigInteger('security_version');
            $table->timestampTz('expires_at', 6);
            $table->timestampTz('revoked_at', 6)->nullable();
            $table->index('user_id', 'auth_trust_user');
            $table->index('expires_at', 'auth_trust_expiry');

            $table->text('browser')->nullable();
            $table->text('platform')->nullable();
            $table->timestampTz('last_used_at', 6)->nullable();
            $table->text('factor_method')->nullable();
            $table->timestampTz('created_at', 6)->nullable();
        });
        DB::statement("ALTER TABLE runa_auth.trusted_devices ADD CHECK (token_hash ~ '^[0-9a-f]{64}$'), ADD CHECK (security_version > 0)");
        DB::statement("ALTER TABLE runa_auth.trusted_devices ADD CHECK (browser IS NULL OR browser IN ('Edge', 'Opera', 'Samsung Internet', 'Firefox', 'Chrome', 'Safari')), ADD CHECK (platform IS NULL OR platform IN ('iOS', 'iPadOS', 'Android', 'Windows', 'ChromeOS', 'macOS', 'Linux'))");
        DB::statement("ALTER TABLE runa_auth.trusted_devices ADD CHECK (factor_method IS NULL OR factor_method IN ('totp', 'recovery', 'email'))");
    }

    public function down(): void
    {
        throw new RuntimeException('Module table recovery requires an explicit plan.');
    }
};
