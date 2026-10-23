<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared.module_transitions', function (Blueprint $table): void {
            $table->bigIncrements('id')->generatedAs()->always();
            $table->uuid('public_id')->unique();
            $table->text('intent');
            $table->text('target');
            $table->bigInteger('generation');
            $table->jsonb('affected');
            $table->text('payload'); // Encrypted preview, private preparation tokens and progress.
            $table->text('status');
            $table->bigInteger('publication_generation')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');
        });
        DB::statement("ALTER TABLE shared.module_transitions ADD CONSTRAINT module_transitions_intent CHECK (intent IN ('enable', 'disable', 'unregister')), ADD CONSTRAINT module_transitions_status CHECK (status IN ('preparing', 'pending', 'compensating', 'recovery_required', 'succeeded', 'refused', 'failed')), ADD CONSTRAINT module_transitions_generation CHECK (generation >= 0 AND (publication_generation IS NULL OR publication_generation > generation)), ADD CONSTRAINT module_transitions_affected CHECK (jsonb_typeof(affected) = 'array')");
        DB::statement("CREATE UNIQUE INDEX module_transitions_active ON shared.module_transitions ((true)) WHERE status IN ('preparing', 'pending', 'compensating', 'recovery_required')");
    }

    public function down(): void
    {
        Schema::dropIfExists('shared.module_transitions');
    }
};
