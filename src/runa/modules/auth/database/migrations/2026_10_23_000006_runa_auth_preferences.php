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
            $table->bigInteger('preference_revision')->default(0);
            $table->boolean('preference_legacy_claimed')->default(false);
        });
        DB::statement('ALTER TABLE runa_auth.users ADD CHECK (preference_revision >= 0)');
        $schema->create('preferences', function (Blueprint $table): void {
            $this->user($table);
            $table->text('scope');
            $table->text('module');
            $table->jsonb('value');
            $table->integer('definition_version');
            $table->timestampsTz(6);
            $table->unique(['user_id', 'scope']);
        }, exposed: false);
        DB::statement('ALTER TABLE runa_auth.preferences ADD CHECK (definition_version > 0), ADD CHECK (octet_length(value::text) <= 32768), ADD CHECK (octet_length(scope) <= 512)');
        $schema->create('delivery_preferences', function (Blueprint $table): void {
            $this->user($table);
            $table->text('module');
            $table->text('category');
            $table->boolean('enabled');
            $table->jsonb('recipients');
            $table->timestampsTz(6);
            $table->unique(['user_id', 'module', 'category']);
        }, exposed: false);
        DB::statement("ALTER TABLE runa_auth.delivery_preferences ADD CHECK (jsonb_typeof(recipients) = 'array' AND jsonb_array_length(recipients) BETWEEN 1 AND 21)");
        $schema->create('security_notices', function (Blueprint $table): void {
            $this->user($table);
            $table->text('category');
            $table->text('event');
            $table->text('primary_email');
            $table->timestampTz('deadline', 6)->nullable();
            $table->timestampTz('expires_at', 6);
            $table->timestampTz('claimed_at', 6)->nullable();
            $table->timestampTz('sent_at', 6)->nullable();
            $table->timestampTz('created_at', 6);
            $table->unique(['user_id', 'category', 'event']);
        });
        $schema->create('notice_frequencies', function (Blueprint $table): void {
            $this->user($table);
            $table->text('category');
            $table->timestampTz('available_at', 6);
            $table->unique(['user_id', 'category']);
        }, exposed: false);
        $schema->create('known_browsers', function (Blueprint $table): void {
            $this->user($table);
            $table->text('fingerprint');
            $table->timestampTz('created_at', 6);
            $table->unique(['user_id', 'fingerprint']);
        }, exposed: false);
        $schema->create('notice_scan', function (Blueprint $table): void {
            $table->bigInteger('last_user_id')->default(0);
        }, exposed: false);
        DB::table('runa_auth.notice_scan')->insert(['last_user_id' => 0]);
        DB::statement('ALTER TABLE runa_auth.notice_scan ADD CHECK (id = 1 AND last_user_id >= 0)');
    }

    private function user(Blueprint $table): void
    {
        $table->bigInteger('user_id');
        $table->foreign('user_id')->references('id')->on('runa_auth.users')->restrictOnDelete()->restrictOnUpdate();
    }

    public function down(): void
    {
        throw new RuntimeException('Preference recovery requires a forward transition.');
    }
};
