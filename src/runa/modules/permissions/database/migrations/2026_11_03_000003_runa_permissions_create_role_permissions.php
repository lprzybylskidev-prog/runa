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
        $schema->create('role_permissions', function (Blueprint $table): void {
            $table->bigInteger('role_id');
            $table->foreign('role_id', 'permissions_role_permissions_role')->references('id')->on('runa_permissions.roles')->restrictOnDelete()->restrictOnUpdate();
            $table->bigInteger('permission_id');
            $table->foreign('permission_id', 'permissions_role_permissions_permission')->references('id')->on('runa_permissions.permissions')->restrictOnDelete()->restrictOnUpdate();
            $table->unique(['role_id', 'permission_id'], 'permissions_role_permissions_unique');
            $table->timestampsTz(6);
            $table->index('permission_id');
        }, exposed: false);
    }

    public function down(): void
    {
        throw new RuntimeException('Permissions table recovery requires an explicit plan.');
    }
};
