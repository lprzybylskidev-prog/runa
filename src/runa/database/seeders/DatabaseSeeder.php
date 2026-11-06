<?php

namespace Runa\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! DB::table('shared.module_registry_state')->exists()) {
            DB::table('shared.module_registry_state')->insert(['generation' => 0]);
        }
        DB::table('shared.application_settings')->insertOrIgnore([
            'singleton' => 1, 'values' => '{}', 'created_at' => now('UTC'), 'updated_at' => now('UTC'),
        ]);
    }
}
