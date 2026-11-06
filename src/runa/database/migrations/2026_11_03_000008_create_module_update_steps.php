<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }
};
