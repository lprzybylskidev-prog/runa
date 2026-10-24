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
        (new ModuleSchema(DB::connection(), new ModuleIdentity('runa.permissions')))->create('development_fixtures', function (Blueprint $table): void {
            $table->text('scenario')->unique();
            $table->uuid('subject');
            $table->timestampTz('created_at', 6);
        }, exposed: false);
    }

    public function down(): void
    {
        throw new RuntimeException('Fixture provenance requires explicit module recovery.');
    }
};
