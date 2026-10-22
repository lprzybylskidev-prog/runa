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
            $table->jsonb('integration_values')->default('{}');
        });
        DB::statement("ALTER TABLE shared.modules ADD CONSTRAINT modules_integration_values CHECK (jsonb_typeof(integration_values) = 'object' AND octet_length(integration_values::text) <= 65536)");
        Schema::table('shared.module_work', function (Blueprint $table): void {
            $table->text('event_listener')->nullable();
            $table->text('delivery_key')->nullable()->unique();
        });
        DB::statement("ALTER TABLE shared.module_work ADD CONSTRAINT module_work_event_delivery CHECK ((event_listener IS NULL AND delivery_key IS NULL) OR (event_listener IS NOT NULL AND delivery_key IS NOT NULL AND event_listener ~ '^[a-z][a-z0-9_]{0,63}$' AND delivery_key ~ '^[0-9a-f]{64}$'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE shared.module_work DROP CONSTRAINT module_work_event_delivery');
        Schema::table('shared.module_work', function (Blueprint $table): void {
            $table->dropColumn(['event_listener', 'delivery_key']);
        });
        DB::statement('ALTER TABLE shared.modules DROP CONSTRAINT modules_integration_values');
        Schema::table('shared.modules', function (Blueprint $table): void {
            $table->dropColumn('integration_values');
        });
    }
};
