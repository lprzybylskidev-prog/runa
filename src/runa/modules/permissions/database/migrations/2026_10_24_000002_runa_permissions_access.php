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
        $schema->create('presets', function (Blueprint $table): void {
            $table->text('code')->unique('permissions_presets_code');
            foreach (['name_pl', 'name_en', 'description_pl', 'description_en'] as $column) {
                $table->text($column);
            }
            $table->bigInteger('version')->default(1);
            $table->timestampsTz(6);
            $table->softDeletesTz('deleted_at', 6);
        });
        $connection->statement(<<<'SQL'
            ALTER TABLE runa_permissions.presets
            ADD CHECK (code ~ '^custom\.[a-z][a-z0-9_]{0,63}$'),
            ADD CHECK (char_length(btrim(name_pl)) BETWEEN 1 AND 160 AND char_length(btrim(name_en)) BETWEEN 1 AND 160),
            ADD CHECK (char_length(description_pl) <= 1000 AND char_length(description_en) <= 1000),
            ADD CHECK (version > 0)
            SQL);
        foreach (['roles' => 'role', 'permissions' => 'permission'] as $members => $key) {
            $schema->create('preset_'.$members, function (Blueprint $table) use ($members, $key): void {
                $table->bigInteger('preset_id');
                $table->bigInteger($key.'_id');
                $table->foreign('preset_id', 'permissions_preset_'.$members.'_preset')->references('id')->on('runa_permissions.presets')->restrictOnDelete()->restrictOnUpdate();
                $table->foreign($key.'_id', 'permissions_preset_'.$members.'_member')->references('id')->on('runa_permissions.'.$members)->restrictOnDelete()->restrictOnUpdate();
                $table->unique(['preset_id', $key.'_id'], 'permissions_preset_'.$members.'_unique');
                $table->index($key.'_id');
                $table->timestampsTz(6);
            }, false);
        }
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
        throw new RuntimeException('Access templates and revision history require explicit recovery.');
    }
};
