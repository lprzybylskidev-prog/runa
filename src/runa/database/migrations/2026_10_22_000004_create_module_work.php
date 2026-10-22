<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared.module_work', function (Blueprint $table): void {
            $table->bigIncrements('id')->generatedAs()->always();
            $table->uuid('public_id')->unique();
            $table->bigInteger('module_id');
            $table->foreign('module_id')->references('id')->on('shared.modules')->restrictOnDelete()->restrictOnUpdate();
            $table->text('operation');
            $table->text('manifest_hash');
            $table->text('correlation_id');
            $table->text('origin_module')->nullable();
            $table->bigInteger('generation');
            $table->text('payload'); // Laravel-encrypted JSON; never a serialized module object.
            $table->text('status');
            $table->integer('attempts')->default(0);
            $table->boolean('cancel_requested')->default(false);
            $table->uuid('lease_token')->nullable();
            $table->timestampTz('lease_until')->nullable();
            $table->timestampTz('available_at');
            $table->text('progress')->default('queued');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');
            $table->index(['module_id', 'status']);
        });
        DB::statement("ALTER TABLE shared.module_work ADD CONSTRAINT module_work_state CHECK (status IN ('pending', 'running', 'succeeded', 'cancelled', 'refused', 'failed', 'recovery_required')), ADD CONSTRAINT module_work_attempts CHECK (attempts BETWEEN 0 AND 100 AND generation >= 0), ADD CONSTRAINT module_work_lease CHECK ((status = 'running' AND lease_token IS NOT NULL AND lease_until IS NOT NULL) OR (status <> 'running' AND lease_token IS NULL AND lease_until IS NULL)), ADD CONSTRAINT module_work_progress CHECK (progress ~ '^[a-z][a-z0-9_]{0,63}$'), ADD CONSTRAINT module_work_payload CHECK (octet_length(payload) <= 131072), ADD CONSTRAINT module_work_uuid CHECK (substring(public_id::text, 15, 1) = '7')");
    }

    public function down(): void
    {
        Schema::dropIfExists('shared.module_work');
    }
};
