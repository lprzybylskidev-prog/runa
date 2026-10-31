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
        (new ModuleSchema(DB::connection(), new ModuleIdentity('runa.auth')))->table('step_up_proofs', function (Blueprint $table): void {
            $table->timestampTz('activity_at', 6)->nullable();
            $table->timestampTz('idle_expires_at', 6)->nullable();
        });
        DB::statement('ALTER TABLE runa_auth.step_up_proofs ADD CHECK ((activity_at IS NULL) = (idle_expires_at IS NULL)), ADD CHECK (activity_at IS NULL OR (activity_at >= verified_at AND idle_expires_at > activity_at))');
    }

    public function down(): void
    {
        throw new RuntimeException('Section activity requires an explicit forward transition.');
    }
};
