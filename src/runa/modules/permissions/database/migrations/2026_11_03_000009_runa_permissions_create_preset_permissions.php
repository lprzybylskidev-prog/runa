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
        $schema->create('preset_permissions', function (Blueprint $table): void {
            $table->bigInteger('preset_id');
            $table->bigInteger('permission_id');
            $table->foreign('preset_id', 'permissions_preset_permissions_preset')->references('id')->on('runa_permissions.presets')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('permission_id', 'permissions_preset_permissions_member')->references('id')->on('runa_permissions.permissions')->restrictOnDelete()->restrictOnUpdate();
            $table->unique(['preset_id', 'permission_id'], 'permissions_preset_permissions_unique');
            $table->index('permission_id');
            $table->timestampsTz(6);
        }, exposed: false);
    }

    public function down(): void
    {
        throw new RuntimeException('Permissions table recovery requires an explicit plan.');
    }
};
