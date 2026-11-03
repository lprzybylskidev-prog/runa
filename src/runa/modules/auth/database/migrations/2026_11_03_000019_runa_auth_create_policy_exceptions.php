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
        $schema->create('policy_exceptions', function (Blueprint $table): void {
            $table->bigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('runa_auth.users')->restrictOnDelete()->restrictOnUpdate();
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
    }

    public function down(): void
    {
        throw new RuntimeException('Module table recovery requires an explicit plan.');
    }
};
