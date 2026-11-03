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
        $schema->create('user_permissions', function (Blueprint $table): void {
            $table->uuid('user_public_id');
            $table->bigInteger('permission_id');
            $table->foreign('permission_id', 'permissions_user_permissions_permission')->references('id')->on('runa_permissions.permissions')->restrictOnDelete()->restrictOnUpdate();
            $table->unique(['user_public_id', 'permission_id'], 'permissions_user_permissions_unique');
            $table->timestampsTz(6);
            $table->index('permission_id');
        }, exposed: false);
        $connection->statement("ALTER TABLE runa_permissions.user_permissions ADD CHECK (user_public_id::text ~ '^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$')");
    }

    public function down(): void
    {
        throw new RuntimeException('Permissions table recovery requires an explicit plan.');
    }
};
