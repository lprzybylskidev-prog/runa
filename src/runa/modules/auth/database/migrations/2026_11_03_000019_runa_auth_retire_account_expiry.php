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
        if (DB::table('runa_auth.users')->whereNotNull('expires_at')->whereNotNull('activate_at')
            ->whereRaw('activate_at >= LEAST(expires_at, deactivate_at)')->exists()) {
            throw new RuntimeException('Account expiry conflicts with activation; resolve the schedule before upgrading.');
        }
        // Preserve the earliest effective access cutoff and invalidate old edit revisions.
        DB::table('runa_auth.users')->whereNotNull('expires_at')->update([
            'deactivate_at' => DB::raw('LEAST(expires_at, deactivate_at)'),
            'version' => DB::raw('version + 1'), 'updated_at' => now(),
        ]);
        (new ModuleSchema(DB::connection(), new ModuleIdentity('runa.auth')))->table('users', function (Blueprint $table): void {
            $table->dropColumn('expires_at');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Account expiry retirement requires an explicit forward transition.');
    }
};
