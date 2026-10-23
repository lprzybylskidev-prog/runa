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
            $table->text('settings_values')->nullable(); // Laravel-encrypted JSON; null means declared defaults.
            $table->bigInteger('settings_revision')->default(0);
        });
        DB::statement('ALTER TABLE shared.modules ADD CONSTRAINT modules_settings CHECK (settings_revision >= 0 AND (settings_values IS NULL OR octet_length(settings_values) <= 131072))');
        DB::statement('ALTER TABLE shared.module_transitions DROP CONSTRAINT module_transitions_intent');
        DB::statement("ALTER TABLE shared.module_transitions ADD CONSTRAINT module_transitions_intent CHECK (intent IN ('enable', 'disable', 'unregister', 'settings'))");
    }

    public function down(): void
    {
        // Retained settings operations must be resolved/removed deliberately before rollback.
        DB::statement('ALTER TABLE shared.module_transitions DROP CONSTRAINT module_transitions_intent');
        DB::statement("ALTER TABLE shared.module_transitions ADD CONSTRAINT module_transitions_intent CHECK (intent IN ('enable', 'disable', 'unregister'))");
        DB::statement('ALTER TABLE shared.modules DROP CONSTRAINT modules_settings');
        Schema::table('shared.modules', fn (Blueprint $table) => $table->dropColumn(['settings_values', 'settings_revision']));
    }
};
