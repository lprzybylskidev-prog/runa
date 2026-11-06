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
        $schema->create('password_history', function (Blueprint $table): void {
            $table->bigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('runa_auth.users')->restrictOnDelete()->restrictOnUpdate();
            $table->text('password_hash');
            $table->timestampTz('created_at', 6);
            $table->index(['user_id', 'id'], 'auth_password_history_user');
        }, exposed: false);
    }

    public function down(): void
    {
        throw new RuntimeException('Module table recovery requires an explicit plan.');
    }
};
