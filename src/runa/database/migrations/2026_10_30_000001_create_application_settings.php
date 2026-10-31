<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared.application_settings', function (Blueprint $table): void {
            $table->bigIncrements('id')->generatedAs()->always();
            $table->smallInteger('singleton')->unique()->default(1);
            $table->bigInteger('revision')->default(1);
            $table->jsonb('values');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');
        });
        DB::statement("ALTER TABLE shared.application_settings ADD CONSTRAINT application_settings_singleton CHECK (singleton = 1), ADD CONSTRAINT application_settings_revision CHECK (revision > 0), ADD CONSTRAINT application_settings_object CHECK (jsonb_typeof(values) = 'object')");
        DB::table('shared.application_settings')->insert(['values' => '{}', 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
    }

    public function down(): void
    {
        Schema::dropIfExists('shared.application_settings');
    }
};
