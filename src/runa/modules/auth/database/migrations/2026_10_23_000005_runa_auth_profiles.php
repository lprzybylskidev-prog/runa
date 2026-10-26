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
            $table->text('avatar_path')->nullable();
            $table->text('avatar_type')->nullable();
            $table->boolean('verification_required')->default(true);
            $table->boolean('verification_forced')->default(false);
        });
        DB::statement("ALTER TABLE runa_auth.users ADD CHECK ((avatar_path IS NULL) = (avatar_type IS NULL)), ADD CHECK (avatar_type IS NULL OR avatar_type IN ('image/jpeg', 'image/png', 'image/webp'))");
        $schema->create('avatar_files', function (Blueprint $table): void {
            $table->bigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('runa_auth.users')->restrictOnDelete()->restrictOnUpdate();
            $table->text('path')->unique();
            $table->timestampTz('available_at', 6);
            $table->timestampTz('created_at', 6);
            $table->index(['available_at', 'id'], 'auth_avatar_cleanup');
        }, exposed: false);
        $schema->create('secondary_emails', function (Blueprint $table): void {
            $table->bigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('runa_auth.users')->restrictOnDelete()->restrictOnUpdate();
            $table->text('email');
            $table->timestampTz('verified_at', 6)->nullable();
            $table->timestampsTz(6);
            $table->softDeletesTz('deleted_at', 6);
        });
        DB::statement("ALTER TABLE runa_auth.secondary_emails ADD CHECK (email = lower(btrim(email)) AND octet_length(email) BETWEEN 3 AND 254 AND email ~ '^[!-~]+@[!-~]+$')");
        DB::statement('CREATE UNIQUE INDEX auth_secondary_account_email ON runa_auth.secondary_emails (user_id, email) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX auth_secondary_address ON runa_auth.secondary_emails (email) WHERE deleted_at IS NULL');
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
        throw new RuntimeException('Profile recovery requires an explicit forward transition.');
    }
};
