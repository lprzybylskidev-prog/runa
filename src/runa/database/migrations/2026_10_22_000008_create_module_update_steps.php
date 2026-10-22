<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shared.module_registry_state', function (Blueprint $table): void {
            $table->text('update_operation')->nullable();
            $table->text('update_owner')->nullable();
            $table->text('update_fingerprint')->nullable();
        });
        DB::statement("ALTER TABLE shared.module_registry_state ADD CHECK (update_operation IS NULL OR update_operation ~ '^[a-f0-9]{40}$'), ADD CONSTRAINT module_registry_update_reservation CHECK ((update_operation IS NULL AND update_owner IS NULL AND update_fingerprint IS NULL) OR (update_operation IS NOT NULL AND update_owner IS NOT NULL AND update_fingerprint IS NOT NULL AND update_owner IN ('runa', 'app') AND update_fingerprint ~ '^[a-f0-9]{64}$'))");
        Schema::create('shared.module_update_steps', function (Blueprint $table): void {
            $table->bigIncrements('id')->generatedAs()->always();
            $table->bigInteger('module_id');
            $table->foreign('module_id')->references('id')->on('shared.modules')->restrictOnDelete()->restrictOnUpdate();
            $table->text('step');
            $table->text('operation');
            $table->text('fingerprint');
            $table->text('status');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');
            $table->unique(['module_id', 'step']);
        });
        DB::statement("ALTER TABLE shared.module_update_steps ADD CHECK (operation ~ '^[a-f0-9]{40}$'), ADD CHECK (fingerprint ~ '^[a-f0-9]{64}$'), ADD CHECK (status IN ('running', 'succeeded'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('shared.module_update_steps');
        Schema::table('shared.module_registry_state', fn (Blueprint $table) => $table->dropColumn(['update_operation', 'update_owner', 'update_fingerprint']));
    }
};
