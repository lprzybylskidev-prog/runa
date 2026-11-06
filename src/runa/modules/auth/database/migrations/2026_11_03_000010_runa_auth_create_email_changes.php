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
        $schema->create('email_changes', function (Blueprint $table): void {
            $table->bigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('runa_auth.users')->restrictOnDelete()->restrictOnUpdate();
            $table->text('old_email');
            $table->text('new_email');
            $table->bigInteger('security_version');
            $table->text('session_hash')->nullable();
            $table->text('session_policy');
            $table->boolean('administrative');
            $table->boolean('old_required');
            $table->boolean('old_confirmed')->default(false);
            $table->boolean('new_confirmed')->default(false);
            $table->timestampTz('expires_at', 6);
            $table->timestampTz('completed_at', 6)->nullable();
            $table->timestampTz('cancelled_at', 6)->nullable();
            $table->timestampsTz(6);
            $table->index(['user_id', 'id'], 'auth_email_change_user');
        });
        DB::statement("ALTER TABLE runa_auth.email_changes ADD CHECK (session_policy IN ('keep_all', 'revoke_others', 'revoke_all')), ADD CHECK (security_version > 0), ADD CHECK (old_email <> new_email), ADD CHECK (NOT administrative OR session_policy = 'revoke_all')");
    }

    public function down(): void
    {
        throw new RuntimeException('Module table recovery requires an explicit plan.');
    }
};
