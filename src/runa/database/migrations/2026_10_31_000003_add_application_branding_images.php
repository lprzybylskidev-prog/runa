<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shared.application_settings', function (Blueprint $table): void {
            $table->binary('branding_logo')->nullable();
            $table->binary('branding_logo_icon')->nullable();
            $table->binary('branding_favicon')->nullable();
        });
        DB::statement('ALTER TABLE shared.application_settings ADD CONSTRAINT application_branding_bounds CHECK ((branding_logo IS NULL) = (branding_logo_icon IS NULL) AND octet_length(branding_logo) <= 524288 AND octet_length(branding_logo_icon) <= 32768 AND octet_length(branding_favicon) <= 32768)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE shared.application_settings DROP CONSTRAINT application_branding_bounds');
        Schema::table('shared.application_settings', fn (Blueprint $table) => $table->dropColumn(['branding_logo', 'branding_logo_icon', 'branding_favicon']));
    }
};
