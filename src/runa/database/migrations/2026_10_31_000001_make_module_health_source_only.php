<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE shared.modules DROP CONSTRAINT modules_health');
        Schema::table('shared.modules', fn (Blueprint $table) => $table->dropColumn(['health_overrides', 'health_revision']));
        DB::statement("ALTER TABLE shared.modules ADD CONSTRAINT modules_health CHECK (jsonb_typeof(health_observations) = 'object' AND octet_length(health_observations::text) <= 65536)");
        // Previous observations were produced under a policy with runtime overrides.
        DB::table('shared.modules')->update(['health_observations' => '{}']);
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE shared.modules DROP CONSTRAINT modules_health');
        Schema::table('shared.modules', function (Blueprint $table): void {
            $table->jsonb('health_overrides')->default('{}');
            $table->bigInteger('health_revision')->default(0);
        });
        DB::statement("ALTER TABLE shared.modules ADD CONSTRAINT modules_health CHECK (health_revision >= 0 AND jsonb_typeof(health_overrides) = 'object' AND octet_length(health_overrides::text) <= 8192 AND jsonb_typeof(health_observations) = 'object' AND octet_length(health_observations::text) <= 65536)");
        DB::table('shared.modules')->update(['health_observations' => '{}']);
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    }
};
