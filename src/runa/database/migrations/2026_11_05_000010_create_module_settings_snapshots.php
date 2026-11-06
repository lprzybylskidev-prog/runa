<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared.module_settings_snapshots', function (Blueprint $table): void {
            $table->bigIncrements('id')->generatedAs()->always();
            $table->uuid('public_id')->unique();
            $table->string('module_id', 128);
            $table->string('scope', 128);
            $table->string('name', 80);
            $table->bigInteger('source_revision');
            $table->char('definition_hash', 64);
            $table->text('settings_values');
            $table->timestampsTz();
            $table->softDeletesTz();
        });
        DB::statement('ALTER TABLE shared.module_settings_snapshots ADD CONSTRAINT module_snapshots_revision CHECK (source_revision >= 0), ADD CONSTRAINT module_snapshots_name CHECK (length(btrim(name)) BETWEEN 1 AND 80), ADD CONSTRAINT module_snapshots_payload CHECK (octet_length(settings_values) <= 131072)');
        DB::statement('CREATE UNIQUE INDEX module_snapshots_live_name ON shared.module_settings_snapshots (module_id, scope, name) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('shared.module_settings_snapshots');
    }
};
