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
        (new ModuleSchema(DB::connection(), new ModuleIdentity('app.reference')))->create('samples', function (Blueprint $table): void {
            $table->string('code', 40)->unique();
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Reference data is retained; use an explicit recovery plan.');
    }
};
