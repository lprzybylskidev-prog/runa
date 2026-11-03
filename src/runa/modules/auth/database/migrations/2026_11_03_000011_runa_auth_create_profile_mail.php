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
        $schema->create('profile_mail', function (Blueprint $table): void {
            $table->bigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('runa_auth.users')->restrictOnDelete()->restrictOnUpdate();
            $table->bigInteger('change_id')->nullable();
            $table->foreign('change_id')->references('id')->on('runa_auth.email_changes')->restrictOnDelete()->restrictOnUpdate();
            $table->bigInteger('secondary_id')->nullable();
            $table->foreign('secondary_id')->references('id')->on('runa_auth.secondary_emails')->restrictOnDelete()->restrictOnUpdate();
            $table->text('purpose');
            $table->text('recipient');
            $table->text('token_hash')->nullable()->unique();
            $table->text('delivery_secret')->nullable();
            $table->bigInteger('security_version');
            $table->timestampTz('expires_at', 6);
            $table->timestampTz('consumed_at', 6)->nullable();
            $table->timestampTz('claimed_at', 6)->nullable();
            $table->timestampTz('sent_at', 6)->nullable();
            $table->timestampsTz(6);
            $table->index(['user_id', 'id'], 'auth_profile_mail_user');
        });
        DB::statement("ALTER TABLE runa_auth.profile_mail ADD CHECK (purpose IN ('primary_new', 'primary_old', 'secondary', 'notice')), ADD CHECK ((purpose IN ('primary_new', 'primary_old') AND change_id IS NOT NULL AND secondary_id IS NULL) OR (purpose = 'secondary' AND secondary_id IS NOT NULL AND change_id IS NULL) OR (purpose = 'notice' AND secondary_id IS NULL AND change_id IS NULL)), ADD CHECK ((purpose = 'notice') = (token_hash IS NULL)), ADD CHECK (security_version > 0)");
    }

    public function down(): void
    {
        throw new RuntimeException('Module table recovery requires an explicit plan.');
    }
};
