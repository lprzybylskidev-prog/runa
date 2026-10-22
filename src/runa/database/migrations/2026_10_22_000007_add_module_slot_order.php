<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shared.module_registry_state', fn (Blueprint $table) => $table->jsonb('slot_order')->default('{}'));
        DB::statement("ALTER TABLE shared.module_registry_state ADD CONSTRAINT module_slot_order CHECK (jsonb_typeof(slot_order) = 'object' AND octet_length(slot_order::text) <= 131072)");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE shared.module_registry_state DROP CONSTRAINT module_slot_order');
        Schema::table('shared.module_registry_state', fn (Blueprint $table) => $table->dropColumn('slot_order'));
    }
};
