<?php

namespace Runa\Database;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Runa\Database\Seeders\DatabaseSeeder;
use RuntimeException;

final class RequiredSeeds
{
    public static function run(Application $app, string $release): void
    {
        if (preg_match('/\A[a-f0-9]{40}\z/D', $release) !== 1) {
            throw new RuntimeException('Required platform seeds need an exact source commit.');
        }
        $database = $app->make(DatabaseManager::class)->connection('pgsql');
        $database->transaction(function () use ($app, $database, $release): void {
            $seeder = DatabaseSeeder::class;
            $inserted = $database->table('shared.platform_seed_runs')->insertOrIgnore(['release_sha' => $release, 'seeder' => $seeder]);
            if ($inserted === 1) {
                $app->make($seeder)->setContainer($app)->__invoke();
            }
        });
    }
}
