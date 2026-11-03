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
        $schema->create('user_roles', function (Blueprint $table): void {
            $table->uuid('user_public_id');
            $table->bigInteger('role_id');
            $table->foreign('role_id', 'permissions_user_roles_role')->references('id')->on('runa_permissions.roles')->restrictOnDelete()->restrictOnUpdate();
            $table->unique(['user_public_id', 'role_id'], 'permissions_user_roles_unique');
            $table->timestampsTz(6);
            $table->index('role_id');
        }, exposed: false);
        $connection->statement("ALTER TABLE runa_permissions.user_roles ADD CHECK (user_public_id::text ~ '^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$')");
    }

    public function down(): void
    {
        throw new RuntimeException('Permissions table recovery requires an explicit plan.');
    }
};
