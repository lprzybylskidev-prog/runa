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
        $schema->create('notice_scan', function (Blueprint $table): void {
            $table->bigInteger('last_user_id')->default(0);
        }, exposed: false);
        DB::statement('ALTER TABLE runa_auth.notice_scan ADD CHECK (id = 1 AND last_user_id >= 0)');
    }

    public function down(): void
    {
        throw new RuntimeException('Module table recovery requires an explicit plan.');
    }
};
