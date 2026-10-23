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
        $schema->create('entry_tokens', function (Blueprint $table): void {
            $table->bigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('runa_auth.users')->restrictOnDelete()->restrictOnUpdate();
            $table->text('purpose');
            $table->text('token_hash')->unique('auth_entry_token_hash');
            $table->text('recipient');
            // Only pending mail can recover the secret; never part of a public API/job envelope.
            $table->text('delivery_secret')->nullable();
            $table->bigInteger('security_version');
            $table->timestampTz('expires_at', 6);
            $table->timestampTz('consumed_at', 6)->nullable();
            $table->timestampTz('invalidated_at', 6)->nullable();
            $table->timestampTz('claimed_at', 6)->nullable();
            $table->timestampTz('sent_at', 6)->nullable();
            $table->timestampTz('remind_at', 6)->nullable();
            $table->integer('reminders')->default(0);
            $table->timestampsTz(6);
            $table->index(['purpose', 'remind_at'], 'auth_entry_reminders');
            $table->index('user_id', 'auth_entry_user');
        });
        DB::statement("ALTER TABLE runa_auth.entry_tokens ADD CHECK (purpose IN ('invitation', 'verification')), ADD CHECK (security_version > 0 AND reminders >= 0), ADD CHECK (token_hash ~ '^[0-9a-f]{64}$')");
    }

    public function down(): void
    {
        throw new RuntimeException('Auth credential history requires explicit recovery.');
    }
};
