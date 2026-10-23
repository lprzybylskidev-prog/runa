<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shared.modules', function (Blueprint $table): void {
            $table->jsonb('health_overrides')->default('{}');
            $table->bigInteger('health_revision')->default(0);
            $table->jsonb('health_observations')->default('{}');
        });
        DB::statement("ALTER TABLE shared.modules ADD CONSTRAINT modules_health CHECK (health_revision >= 0 AND jsonb_typeof(health_overrides) = 'object' AND octet_length(health_overrides::text) <= 8192 AND jsonb_typeof(health_observations) = 'object' AND octet_length(health_observations::text) <= 65536)");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE shared.modules DROP CONSTRAINT modules_health');
        Schema::table('shared.modules', fn (Blueprint $table) => $table->dropColumn(['health_overrides', 'health_revision', 'health_observations']));
    }
};
