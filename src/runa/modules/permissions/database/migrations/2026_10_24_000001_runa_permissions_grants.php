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
        foreach (['permissions', 'roles'] as $name) {
            $schema->create($name, function (Blueprint $table) use ($name): void {
                $table->text('code')->unique('permissions_'.$name.'_code');
                foreach (['name_pl', 'name_en', 'description_pl', 'description_en'] as $column) {
                    $table->text($column);
                }
                $table->text('module_id')->nullable();
                $table->boolean('system')->default(false);
                $table->bigInteger('version')->default(1);
                $table->timestampsTz(6);
                $table->softDeletesTz('deleted_at', 6);
            });
            $connection->statement('ALTER TABLE runa_permissions.'.$name.' '.<<<'SQL'
                ADD CHECK (code ~ '^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)*$' AND octet_length(code) <= 128),
                ADD CHECK (char_length(btrim(name_pl)) BETWEEN 1 AND 160 AND char_length(btrim(name_en)) BETWEEN 1 AND 160),
                ADD CHECK (char_length(description_pl) <= 1000 AND char_length(description_en) <= 1000),
                ADD CHECK (version > 0),
                ADD CHECK (module_id IS NULL OR (module_id ~ '^(runa|app)\.[a-z][a-z0-9_]*$' AND starts_with(code, module_id || '.'))),
                ADD CHECK (NOT system OR (module_id = 'runa.permissions' AND code = 'runa.permissions.super_admin' AND deleted_at IS NULL))
                SQL);
        }
        foreach (['role_permissions', 'user_roles', 'user_permissions'] as $name) {
            $schema->create($name, function (Blueprint $table) use ($name): void {
                $keys = [];
                foreach (['role' => 'roles', 'permission' => 'permissions'] as $key => $target) {
                    if (($key === 'role' && $name !== 'user_permissions') || ($key === 'permission' && $name !== 'user_roles')) {
                        $table->bigInteger($key.'_id');
                        $table->foreign($key.'_id', 'permissions_'.$name.'_'.$key)->references('id')
                            ->on('runa_permissions.'.$target)->restrictOnDelete()->restrictOnUpdate();
                        $keys[] = $key.'_id';
                    }
                }
                if ($name !== 'role_permissions') {
                    $table->uuid('user_public_id');
                    array_unshift($keys, 'user_public_id');
                }
                $table->unique($keys, 'permissions_'.$name.'_unique');
                $table->timestampsTz(6);
                if ($name === 'role_permissions') {
                    $table->index('permission_id');
                } elseif ($name === 'user_roles') {
                    $table->index('role_id');
                } else {
                    $table->index('permission_id');
                }
            }, false);
            if ($name !== 'role_permissions') {
                $connection->statement('ALTER TABLE runa_permissions.'.$name." ADD CHECK (user_public_id::text ~ '^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$')");
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Permissions definitions and assignments require explicit recovery.');
    }
};
