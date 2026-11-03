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

            $table->binary('branding_logo')->nullable();
            $table->binary('branding_logo_icon')->nullable();
            $table->binary('branding_favicon')->nullable();
        });
        DB::statement("ALTER TABLE shared.application_settings ADD CONSTRAINT application_settings_singleton CHECK (singleton = 1), ADD CONSTRAINT application_settings_revision CHECK (revision > 0), ADD CONSTRAINT application_settings_object CHECK (jsonb_typeof(values) = 'object')");
        DB::statement('ALTER TABLE shared.application_settings ADD CONSTRAINT application_branding_bounds CHECK ((branding_logo IS NULL) = (branding_logo_icon IS NULL) AND octet_length(branding_logo) <= 524288 AND octet_length(branding_logo_icon) <= 32768 AND octet_length(branding_favicon) <= 32768)');
    }

    public function down(): void
    {
        Schema::dropIfExists('shared.application_settings');
    }
};
