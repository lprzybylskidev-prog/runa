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
        $schema->table('users', fn (Blueprint $table) => $table->boolean('operator_recovery_pending')->default(false));
        $schema->create('policy_versions', function (Blueprint $table): void {
            $table->bigInteger('revision')->unique();
            $table->text('operation');
            $table->text('phase');
            $table->jsonb('values');
            $table->jsonb('changes');
            $table->jsonb('consequences');
            $table->jsonb('actor')->nullable();
            $table->timestampTz('created_at', 6);
        });
        DB::statement("ALTER TABLE runa_auth.policy_versions ADD CHECK (revision >= 0), ADD CHECK (phase IN ('baseline', 'publish', 'restore', 'upgrade')), ADD CHECK (jsonb_typeof(values) = 'object')");
        $schema->create('policy_exceptions', function (Blueprint $table): void {
            $this->user($table);
            $table->text('key');
            $table->integer('value');
            $table->text('reason');
            $table->uuid('actor');
            $table->bigInteger('policy_revision');
            $table->bigInteger('version');
            $table->timestampTz('expires_at', 6);
            $table->timestampTz('revoked_at', 6)->nullable();
            $table->timestampsTz(6);
        });
        DB::statement("ALTER TABLE runa_auth.policy_exceptions ADD CHECK (key IN ('password_expiry_days', 'session_idle_minutes')), ADD CHECK ((key = 'password_expiry_days' AND value BETWEEN 1 AND 3650) OR (key = 'session_idle_minutes' AND value BETWEEN 1 AND 43200)), ADD CHECK (version > 0 AND policy_revision >= 0), ADD CHECK (octet_length(reason) BETWEEN 1 AND 1000)");
        DB::statement('CREATE UNIQUE INDEX auth_policy_exception_current ON runa_auth.policy_exceptions (user_id, key) WHERE revoked_at IS NULL');
        $schema->create('operator_tokens', function (Blueprint $table): void {
            $this->user($table);
            $table->text('token_hash')->unique();
            $table->text('purpose');
            $table->bigInteger('security_version');
            $table->timestampTz('expires_at', 6);
            $table->timestampTz('consumed_at', 6)->nullable();
            $table->timestampTz('created_at', 6);
        });
        DB::statement("ALTER TABLE runa_auth.operator_tokens ADD CHECK (purpose IN ('initialize', 'recover')), ADD CHECK (security_version > 0), ADD CHECK (token_hash ~ '^[a-f0-9]{64}$')");
        $schema->create('initialization', function (Blueprint $table): void {
            $table->uuid('subject')->nullable();
            $table->timestampTz('completed_at', 6)->nullable();
        }, exposed: false);
        DB::statement('ALTER TABLE runa_auth.initialization ADD CHECK (id = 1), ADD CHECK ((subject IS NULL) = (completed_at IS NULL))');
        DB::table('runa_auth.initialization')->insert(['subject' => null, 'completed_at' => null]);
    }

    private function user(Blueprint $table): void
    {
        $table->bigInteger('user_id');
        $table->foreign('user_id')->references('id')->on('runa_auth.users')->restrictOnDelete()->restrictOnUpdate();
    }

    public function down(): void
    {
        throw new RuntimeException('Auth policy/operator recovery requires a forward transition.');
    }
};
