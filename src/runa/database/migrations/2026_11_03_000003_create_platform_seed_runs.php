<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared.platform_seed_runs', function (Blueprint $table): void {
            $table->bigIncrements('id')->generatedAs()->always();
            $table->text('release_sha');
            $table->text('seeder');
            $table->unique(['release_sha', 'seeder']);
        });
        DB::statement("ALTER TABLE shared.platform_seed_runs ADD CONSTRAINT platform_seed_runs_release_sha CHECK (release_sha ~ '^[a-f0-9]{40}$')");
    }

    public function down(): void
    {
        Schema::dropIfExists('shared.platform_seed_runs');
    }
};
