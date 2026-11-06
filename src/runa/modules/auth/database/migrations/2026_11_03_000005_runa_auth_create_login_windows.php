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
        throw new RuntimeException('Module table recovery requires an explicit plan.');
    }
};
