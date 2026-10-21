<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Runa\Database\DatabaseScope;
use Tests\Fixtures\DatabaseOwnership;

it('keeps migration, wipe, reset and seed operations within their database owner', function (): void {
    $fixture = new DatabaseOwnership;
    try {
        $platformBefore = DB::table('shared.migrations')->orderBy('id')->get()->toJson();
        $job = DB::table('shared.jobs')->insertGetId(['queue' => '9c', 'payload' => 'protected-platform-sentinel', 'attempts' => 0, 'available_at' => 0, 'created_at' => 0]);
        $preserved = function () use ($platformBefore, $job): void {
            expect(DB::table('shared.migrations')->orderBy('id')->get()->toJson())->toBe($platformBefore);
            expect(DB::table('shared.jobs')->where('id', $job)->value('payload'))->toBe('protected-platform-sentinel');
        };
        $fixture->command(false, ['migrate', '--force']);
        $fixture->command(false, ['migrate:fresh', '--seed', '--force']);
        expect(DB::table('app_9c.records')->pluck('label')->all())->toBe(['application-seed']);
        $preserved();
        foreach (['migrate:rollback', 'migrate:reset', 'db:wipe'] as $command) {
            $fixture->command(false, [$command, '--force']);
            expect(Schema::hasTable('app_9c.records'))->toBeFalse();
            $preserved();
            $fixture->command(false, ['migrate', '--force']);
        }
        $fixture->command(false, ['migrate:refresh', '--seed', '--force']);
        $preserved();
        $fixture->command(false, ['runa:seed']);
        expect(DB::table('app_9c.records')->count())->toBe(2); // Only the explicitly registered application seeder.
        $appHistory = DB::table('shared.application_migrations')->orderBy('id')->get()->toJson();
        $appRows = DB::table('app_9c.records')->get()->toJson();
        $appPreserved = function () use ($appHistory, $appRows): void {
            expect(DB::table('shared.application_migrations')->orderBy('id')->get()->toJson())->toBe($appHistory);
            expect(DB::table('app_9c.records')->get()->toJson())->toBe($appRows);
        };
        foreach (['migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback', 'db:wipe'] as $command) {
            $fixture->command(true, [$command, '--force']);
            $appPreserved();
            $fixture->command(true, ['migrate', '--force']);
        }
        $fixture->command(true, ['db:seed', '--force']);
        expect(DB::table('shared.jobs')->where('payload', 'platform-seed')->count())->toBe(1);
        $appPreserved();
    } finally {
        DB::statement('DROP TABLE IF EXISTS app_9c.records');
        DB::statement('DROP SCHEMA IF EXISTS app_9c RESTRICT');
        DB::statement('DROP TABLE IF EXISTS shared.application_migrations');
        $fixture->remove();
    }
});

it('rejects owner path, connection, seeder and dump bypasses before destructive work', function (): void {
    $fixture = new DatabaseOwnership;
    try {
        $fixture->command(false, ['migrate:fresh', '--seed', '--force']);
        $history = DB::table('shared.migrations')->get()->toJson();
        $app = DB::table('app_9c.records')->get()->toJson();
        symlink($fixture->root.'/runa/database/migrations', $fixture->root.'/app/database/migrations/escape');
        $variants = [
            ['migrate', '--path=runa/database/migrations'],
            ['migrate', '--realpath', '--path='.$fixture->root.'/runa/database/migrations'],
            ['migrate', '--path=app/database/migrations/escape'],
            ['migrate', '--graceful', '--path=runa/database/migrations'],
            ['migrate:fresh', '--path=runa/database/migrations'],
            ['migrate:refresh', '--path=runa/database/migrations'],
            ['migrate:reset', '--path=runa/database/migrations'],
            ['migrate:rollback', '--path=runa/database/migrations'],
            ['migrate:fresh', '--seeder=Runa\\Database\\Seeders\\DatabaseSeeder'],
            ['db:seed', '--class=Runa\\Database\\Seeders\\DatabaseSeeder'],
            ['db:seed', 'Runa\\Database\\Seeders\\DatabaseSeeder'],
            ['db:wipe', '--drop-views'], ['db:wipe', '--drop-types'],
            ['migrate:fresh', '--schema-path='.$fixture->root.'/global.sql'],
            ['migrate:fresh', '--database=other'],
        ];
        foreach ($variants as $arguments) {
            $fixture->command(false, [...$arguments, '--force'], false);
            expect(DB::table('shared.migrations')->get()->toJson())->toBe($history);
            expect(DB::table('app_9c.records')->get()->toJson())->toBe($app);
        }
        $fixture->command(true, ['migrate:reset', '--path=app/database/migrations', '--force'], false);
        $fixture->command(true, ['db:seed', '--class=Database\\Seeders\\DatabaseSeeder', '--force']); // Owner default maps to platform root.
        expect(DB::table('app_9c.records')->get()->toJson())->toBe($app);
        symlink($fixture->root.'/runa/database/migrations/2026_10_17_000001_create_queue_tables.php', $fixture->root.'/app/database/migrations/2099_01_01_000002_escape.php');
        $fixture->command(false, ['migrate:fresh', '--force'], false);
        expect(DB::table('shared.migrations')->get()->toJson())->toBe($history);
        expect(DB::table('app_9c.records')->get()->toJson())->toBe($app);
    } finally {
        DB::statement('DROP TABLE IF EXISTS app_9c.records');
        DB::statement('DROP SCHEMA IF EXISTS app_9c RESTRICT');
        DB::statement('DROP TABLE IF EXISTS shared.application_migrations');
        $fixture->remove();
    }
});

it('refuses cross-owner dependent objects without cascade or partial history loss', function (): void {
    $fixture = new DatabaseOwnership;
    try {
        $fixture->command(false, ['migrate:fresh', '--seed', '--force']);
        DB::statement('ALTER TABLE shared.jobs ADD COLUMN app_9c_id bigint REFERENCES app_9c.records(id) ON DELETE RESTRICT ON UPDATE RESTRICT');
        $history = DB::table('shared.application_migrations')->get()->toJson();
        $fixture->command(false, ['migrate:fresh', '--force'], false);
        expect(DB::table('shared.application_migrations')->get()->toJson())->toBe($history);
        expect(DB::table('app_9c.records')->count())->toBe(1);
        DB::statement('ALTER TABLE shared.jobs DROP COLUMN app_9c_id');
        DB::statement('ALTER TABLE app_9c.records ADD COLUMN job_id bigint REFERENCES shared.jobs(id) ON DELETE RESTRICT ON UPDATE RESTRICT');
        $platform = DB::table('shared.migrations')->get()->toJson();
        $fixture->command(true, ['migrate:fresh', '--force'], false);
        expect(DB::table('shared.migrations')->get()->toJson())->toBe($platform);
        expect(Schema::hasTable('shared.jobs'))->toBeTrue();
        expect(DB::table('app_9c.records')->count())->toBe(1);
        DB::statement('ALTER TABLE app_9c.records DROP COLUMN job_id');
    } finally {
        if (Schema::hasColumn('shared.jobs', 'app_9c_id')) {
            DB::statement('ALTER TABLE shared.jobs DROP COLUMN app_9c_id');
        }
        DB::statement('DROP TABLE IF EXISTS app_9c.records');
        DB::statement('DROP SCHEMA IF EXISTS app_9c RESTRICT');
        DB::statement('DROP TABLE IF EXISTS shared.application_migrations');
        $fixture->remove();
    }
});

it('records required platform seeds atomically and skips repeats without touching application data', function (): void {
    $fixture = new DatabaseOwnership;
    try {
        $fixture->command(false, ['migrate:fresh', '--seed', '--force']);
        $app = DB::table('app_9c.records')->get()->toJson();
        DB::table('shared.jobs')->where('payload', 'platform-seed')->delete();
        $release = str_repeat('a', 40);
        $retry = str_repeat('b', 40);
        DB::table('shared.platform_seed_runs')->whereIn('release_sha', [$release, $retry])->delete();
        expect($fixture->installSeeds($release)->getExitCode())->toBe(0);
        expect($fixture->installSeeds($release)->getExitCode())->toBe(0);
        expect(DB::table('shared.jobs')->where('payload', 'platform-seed')->count())->toBe(1);
        expect(DB::table('shared.platform_seed_runs')->where('release_sha', $release)->count())->toBe(1);
        $failed = $fixture->installSeeds($retry, true);
        expect($failed->isSuccessful())->toBeFalse();
        expect(DB::table('shared.jobs')->where('payload', 'platform-seed')->count())->toBe(1);
        expect(DB::table('shared.platform_seed_runs')->where('release_sha', $retry)->count())->toBe(0);
        expect($fixture->installSeeds($retry)->getExitCode())->toBe(0);
        expect(DB::table('shared.jobs')->where('payload', 'platform-seed')->count())->toBe(2);
        expect(DB::table('app_9c.records')->get()->toJson())->toBe($app);
    } finally {
        DB::statement('DROP TABLE IF EXISTS app_9c.records');
        DB::statement('DROP SCHEMA IF EXISTS app_9c RESTRICT');
        DB::statement('DROP TABLE IF EXISTS shared.application_migrations');
        $fixture->remove();
    }
});

it('fails closed for unknown legacy history and unowned public tables', function (): void {
    $fixture = new DatabaseOwnership;
    try {
        $fixture->command(false, ['migrate:fresh', '--seed', '--force']);
        DB::table('shared.migrations')->insert(['migration' => '2099_unknown_legacy_app_table', 'batch' => 999]);
        foreach ([false, true] as $platform) {
            $fixture->command($platform, ['migrate:fresh', '--force'], false);
        }
        expect(DB::table('app_9c.records')->count())->toBe(1);
        expect(Schema::hasTable('shared.jobs'))->toBeTrue();
        DB::table('shared.migrations')->where('migration', '2099_unknown_legacy_app_table')->delete();
        DB::statement('CREATE TABLE public.unowned_9c (id bigint)');
        $fixture->command(false, ['migrate:fresh', '--force'], false);
        expect(DB::table('app_9c.records')->count())->toBe(1);
        expect(Schema::hasTable('public.unowned_9c'))->toBeTrue();
    } finally {
        DB::table('shared.migrations')->where('migration', '2099_unknown_legacy_app_table')->delete();
        DB::statement('DROP TABLE IF EXISTS public.unowned_9c');
        DB::statement('DROP TABLE IF EXISTS app_9c.records');
        DB::statement('DROP SCHEMA IF EXISTS app_9c RESTRICT');
        DB::statement('DROP TABLE IF EXISTS shared.application_migrations');
        $fixture->remove();
    }
});

it('keeps the protected platform table inventory synchronized with actual platform migrations', function (): void {
    $fixture = new DatabaseOwnership;
    try {
        // Start from no app tables, then rebuild only the platform under the test guard.
        $fixture->command(false, ['db:wipe', '--force']);
        $fixture->command(true, ['migrate:fresh', '--force']);
        $tables = DB::select("SELECT schemaname || '.' || tablename AS name FROM pg_tables WHERE schemaname NOT LIKE 'pg_%' AND schemaname <> 'information_schema' ORDER BY 1");
        $expected = DatabaseScope::PLATFORM_TABLES;
        sort($expected);
        expect(array_column($tables, 'name'))->toBe($expected);
    } finally {
        $fixture->remove();
    }
});
