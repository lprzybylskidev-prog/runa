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
        // This roadmap explicitly retains existing example data; no rebuild/reset.
        (new ModuleSchema(DB::connection(), new ModuleIdentity('app.reference')))->table('samples', function (Blueprint $table): void {
            $table->bigInteger('revision')->default(1);
        });
        DB::statement('ALTER TABLE app_reference.samples ADD CONSTRAINT reference_sample_revision_positive CHECK (revision > 0)');
    }

    public function down(): void
    {
        throw new RuntimeException('Reference data is retained; use an explicit recovery plan.');
    }
};
