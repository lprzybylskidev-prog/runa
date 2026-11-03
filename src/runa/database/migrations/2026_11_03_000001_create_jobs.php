<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared.jobs', function (Blueprint $table): void {
            $table->bigIncrements('id')->generatedAs()->always();
            $table->text('queue')->index();
            $table->text('payload');
            $table->smallInteger('attempts')->default(0);
            $table->bigInteger('reserved_at')->nullable();
            $table->bigInteger('available_at');
            $table->bigInteger('created_at');
        });
        DB::statement('ALTER TABLE shared.jobs ADD CONSTRAINT jobs_attempts_range CHECK (attempts BETWEEN 0 AND 255)');
    }

    public function down(): void
    {
        Schema::dropIfExists('shared.jobs');
    }
};
