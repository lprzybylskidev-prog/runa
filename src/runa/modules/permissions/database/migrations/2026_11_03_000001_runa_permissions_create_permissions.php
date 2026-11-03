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
        $schema->create('permissions', function (Blueprint $table): void {
            $table->text('code')->unique('permissions_permissions_code');
            foreach (['name_pl', 'name_en', 'description_pl', 'description_en'] as $column) {
                $table->text($column);
            }
            $table->text('module_id')->nullable();
            $table->boolean('system')->default(false);
            $table->bigInteger('version')->default(1);
            $table->timestampsTz(6);
            $table->softDeletesTz('deleted_at', 6);
        });
        $connection->statement(<<<'SQL'
        ALTER TABLE runa_permissions.permissions
        ADD CHECK (code ~ '^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)*$' AND octet_length(code) <= 128),
        ADD CHECK (char_length(btrim(name_pl)) BETWEEN 1 AND 160 AND char_length(btrim(name_en)) BETWEEN 1 AND 160),
        ADD CHECK (char_length(description_pl) <= 1000 AND char_length(description_en) <= 1000),
        ADD CHECK (version > 0),
        ADD CHECK (module_id IS NULL OR (module_id ~ '^(runa|app)\.[a-z][a-z0-9_]*$' AND starts_with(code, module_id || '.'))),
        ADD CHECK (NOT system OR (module_id = 'runa.permissions' AND code = 'runa.permissions.super_admin' AND deleted_at IS NULL))
        SQL);
    }

    public function down(): void
    {
        throw new RuntimeException('Permissions table recovery requires an explicit plan.');
    }
};
