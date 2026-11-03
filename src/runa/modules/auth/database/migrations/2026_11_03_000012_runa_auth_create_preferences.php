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
        $schema->create('preferences', function (Blueprint $table): void {
            $table->bigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('runa_auth.users')->restrictOnDelete()->restrictOnUpdate();
            $table->text('scope');
            $table->text('module');
            $table->jsonb('value');
            $table->integer('definition_version');
            $table->timestampsTz(6);
            $table->unique(['user_id', 'scope']);
        }, exposed: false);
        DB::statement('ALTER TABLE runa_auth.preferences ADD CHECK (definition_version > 0), ADD CHECK (octet_length(value::text) <= 32768), ADD CHECK (octet_length(scope) <= 512)');
    }

    public function down(): void
    {
        throw new RuntimeException('Module table recovery requires an explicit plan.');
    }
};
