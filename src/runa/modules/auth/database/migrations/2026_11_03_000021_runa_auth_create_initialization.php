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
        $schema->create('initialization', function (Blueprint $table): void {
            $table->uuid('subject')->nullable();
            $table->timestampTz('completed_at', 6)->nullable();
        }, exposed: false);
        DB::statement('ALTER TABLE runa_auth.initialization ADD CHECK (id = 1), ADD CHECK ((subject IS NULL) = (completed_at IS NULL))');
    }

    public function down(): void
    {
        throw new RuntimeException('Module table recovery requires an explicit plan.');
    }
};
