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
        $schema->create('policy_versions', function (Blueprint $table): void {
            $table->bigInteger('revision')->unique();
            $table->text('operation');
            $table->text('phase');
            $table->jsonb('values');
            $table->jsonb('changes');
            $table->jsonb('consequences');
            $table->jsonb('actor')->nullable();
            $table->timestampTz('created_at', 6);
        });
        DB::statement("ALTER TABLE runa_auth.policy_versions ADD CHECK (revision >= 0), ADD CHECK (phase IN ('baseline', 'publish', 'restore', 'upgrade')), ADD CHECK (jsonb_typeof(values) = 'object')");
    }

    public function down(): void
    {
        throw new RuntimeException('Module table recovery requires an explicit plan.');
    }
};
