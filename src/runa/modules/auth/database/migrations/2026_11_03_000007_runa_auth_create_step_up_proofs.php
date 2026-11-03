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

            $table->timestampTz('activity_at', 6)->nullable();
            $table->timestampTz('idle_expires_at', 6)->nullable();
        }, exposed: false);
        DB::statement("ALTER TABLE runa_auth.step_up_proofs ADD CHECK (requirement IN ('password', 'second_factor', 'both')), ADD CHECK ((requirement = 'password' AND factor_method IS NULL) OR (requirement <> 'password' AND factor_method IS NOT NULL AND factor_method IN ('totp', 'recovery', 'email'))), ADD CHECK (security_version > 0), ADD CHECK (expires_at > verified_at), ADD CHECK (session_hash ~ '^[0-9a-f]{64}$')");
        DB::statement('ALTER TABLE runa_auth.step_up_proofs ADD CHECK ((activity_at IS NULL) = (idle_expires_at IS NULL)), ADD CHECK (activity_at IS NULL OR (activity_at >= verified_at AND idle_expires_at > activity_at))');
    }

    public function down(): void
    {
        throw new RuntimeException('Module table recovery requires an explicit plan.');
    }
};
