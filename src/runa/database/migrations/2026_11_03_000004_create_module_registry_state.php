<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared.module_registry_state', function (Blueprint $table): void {
            $table->bigIncrements('id')->generatedAs()->always();
            $table->bigInteger('generation')->default(0);

            $table->jsonb('slot_order')->default('{}');
            $table->text('update_operation')->nullable();
            $table->text('update_owner')->nullable();
            $table->text('update_fingerprint')->nullable();
        });
        DB::statement('ALTER TABLE shared.module_registry_state ADD CONSTRAINT module_registry_singleton CHECK (id = 1 AND generation >= 0)');
        DB::statement("ALTER TABLE shared.module_registry_state ADD CONSTRAINT module_slot_order CHECK (jsonb_typeof(slot_order) = 'object' AND octet_length(slot_order::text) <= 131072)");
        DB::statement("ALTER TABLE shared.module_registry_state ADD CHECK (update_operation IS NULL OR update_operation ~ '^[a-f0-9]{40}$'), ADD CONSTRAINT module_registry_update_reservation CHECK ((update_operation IS NULL AND update_owner IS NULL AND update_fingerprint IS NULL) OR (update_operation IS NOT NULL AND update_owner IS NOT NULL AND update_fingerprint IS NOT NULL AND update_owner IN ('runa', 'app') AND update_fingerprint ~ '^[a-f0-9]{64}$'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('shared.module_registry_state');
    }
};
