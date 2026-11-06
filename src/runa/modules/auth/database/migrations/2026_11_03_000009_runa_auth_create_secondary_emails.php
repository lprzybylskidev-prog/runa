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
    }

    public function down(): void
    {
        throw new RuntimeException('Module table recovery requires an explicit plan.');
    }
};
