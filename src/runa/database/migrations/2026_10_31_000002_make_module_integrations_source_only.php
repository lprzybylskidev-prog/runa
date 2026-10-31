<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE shared.modules DROP CONSTRAINT modules_integration_values');
        Schema::table('shared.modules', fn (Blueprint $table) => $table->dropColumn('integration_values'));
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    }

    public function down(): void
    {
        Schema::table('shared.modules', fn (Blueprint $table) => $table->jsonb('integration_values')->default('{}'));
        DB::statement("ALTER TABLE shared.modules ADD CONSTRAINT modules_integration_values CHECK (jsonb_typeof(integration_values) = 'object' AND octet_length(integration_values::text) <= 65536)");
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    }
};
