<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared.modules', function (Blueprint $table): void {
            $table->bigIncrements('id')->generatedAs()->always();
            $table->uuid('public_id')->unique();
            $table->text('module_id')->unique();
            $table->text('owner');
            $table->text('code');
            $table->unique(['owner', 'code']);
            $table->text('kind');
            $table->boolean('registered')->default(true);
            $table->boolean('source_present')->default(true);
            $table->boolean('enabled');
            $table->text('source_version');
            $table->text('installed_version')->nullable();
            $table->text('source_path');
            $table->text('manifest_hash');
            $table->jsonb('manifest');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            $table->jsonb('health_observations')->default('{}');

            $table->text('settings_values')->nullable(); // Laravel-encrypted JSON; null means declared defaults.
            $table->bigInteger('settings_revision')->default(0);
        });
        DB::statement(<<<'SQL'
        ALTER TABLE shared.modules
            ADD CONSTRAINT modules_identity CHECK (owner IN ('runa', 'app') AND code ~ '^[a-z][a-z0-9]*(_[a-z][a-z0-9]*)*$' AND octet_length(code) <= 58 AND module_id = owner || '.' || code),
            ADD CONSTRAINT modules_kind CHECK (kind IN ('core', 'optional') AND (kind <> 'core' OR enabled)),
            ADD CONSTRAINT modules_source_path CHECK (source_path = owner || '/modules/' || code),
            ADD CONSTRAINT modules_manifest_hash CHECK (manifest_hash ~ '^[a-f0-9]{64}$'),
            ADD CONSTRAINT modules_manifest_object CHECK (jsonb_typeof(manifest) = 'object'),
            ADD CONSTRAINT modules_versions CHECK (source_version ~ '^(0|[1-9][0-9]{0,8})\.(0|[1-9][0-9]{0,8})\.(0|[1-9][0-9]{0,8})$' AND (installed_version IS NULL OR installed_version ~ '^(0|[1-9][0-9]{0,8})\.(0|[1-9][0-9]{0,8})\.(0|[1-9][0-9]{0,8})$'))
        SQL);
        DB::statement('ALTER TABLE shared.modules ADD CONSTRAINT modules_settings CHECK (settings_revision >= 0 AND (settings_values IS NULL OR octet_length(settings_values) <= 131072))');
        DB::statement("ALTER TABLE shared.modules ADD CONSTRAINT modules_health CHECK (jsonb_typeof(health_observations) = 'object' AND octet_length(health_observations::text) <= 65536)");
    }

    public function down(): void
    {
        Schema::dropIfExists('shared.modules');
    }
};
