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
        $schema = new ModuleSchema($connection, new ModuleIdentity('runa.permissions'));
        $schema->create('user_access', function (Blueprint $table): void {
            $table->uuid('user_public_id')->unique('permissions_user_access_subject');
            $table->bigInteger('version');
            $table->timestampTz('updated_at', 6);
        }, false);
        $connection->statement(<<<'SQL'
                    ALTER TABLE runa_permissions.user_access ADD CHECK (version > 0),
                    ADD CHECK (user_public_id::text ~ '^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$')
                    SQL);
    }

    public function down(): void
    {
        throw new RuntimeException('Permissions table recovery requires an explicit plan.');
    }
};
