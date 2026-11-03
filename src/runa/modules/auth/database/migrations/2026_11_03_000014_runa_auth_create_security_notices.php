<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Runa\Support\Modules\Data\ModuleSchema;
use Runa\Support\Modules\ModuleIdentity;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection();
        $schema = new ModuleSchema($connection, new ModuleIdentity('runa.auth'));
        $schema->create('security_notices', function (Blueprint $table): void {
            $table->bigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('runa_auth.users')->restrictOnDelete()->restrictOnUpdate();
            $table->text('category');
            $table->text('event');
            $table->text('primary_email');
            $table->timestampTz('deadline', 6)->nullable();
            $table->timestampTz('expires_at', 6);
            $table->timestampTz('claimed_at', 6)->nullable();
            $table->timestampTz('sent_at', 6)->nullable();
            $table->timestampTz('created_at', 6);
            $table->unique(['user_id', 'category', 'event']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Module table recovery requires an explicit plan.');
    }
};
