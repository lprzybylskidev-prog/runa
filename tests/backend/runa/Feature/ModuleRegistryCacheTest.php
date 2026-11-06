<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Runa\ModuleFramework\ModuleDiscovery;
use Runa\ModuleFramework\ModuleRegistry;
use Tests\Fixtures\ModuleSources;

it('caches committed generations while discarding rolled back generations and refreshing another worker', function (): void {
    $sources = new ModuleSources;
    $registry = new ModuleRegistry(DB::connection(), new ModuleDiscovery($sources->root));
    $reader = new ModuleRegistry(DB::connection(), new ModuleDiscovery($sources->root));
    $publicId = null;
    // Use the platform-owned test database, but preserve any preexisting registrations.
    expect(DB::table('shared.modules')->count())->toBe(0);
    try {
        $empty = $registry->snapshot();
        expect($registry->snapshot())->toBe($empty);
        $sources->add('app', 'cache_rolled_back');
        expect(fn () => DB::transaction(function () use ($registry): void {
            $registry->synchronize('app');
            throw new RuntimeException('Roll back publication.');
        }))->toThrow(RuntimeException::class);
        expect($registry->snapshot())->toEqual($empty);
        File::deleteDirectory($sources->root.'/app/modules/cache_rolled_back');
        $reader->snapshot();
        $sources->add('app', 'cache_committed');
        $published = $registry->synchronize('app');
        $publicId = $published->modules['app.cache_committed']->publicId;
        expect($registry->snapshot())->toBe($published)
            ->and($published->modules)->not->toHaveKey('app.cache_rolled_back')
            ->and($reader->snapshot()->generation)->toBe($published->generation)
            ->and($reader->snapshot()->modules)->toHaveKey('app.cache_committed');
    } finally {
        if ($publicId !== null) {
            DB::transaction(function () use ($publicId): void {
                DB::table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first();
                DB::table('shared.modules')->where('public_id', $publicId)->delete();
                DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
            });
        }
        $sources->remove();
    }
});

it('bounds locked registry reads and observes same-connection writes before generation publication', function (): void {
    $sources = new ModuleSources;
    $db = DB::connection();
    $registry = new ModuleRegistry($db, new ModuleDiscovery($sources->root));
    $db->beginTransaction();
    try {
        $sources->add('app', 'cache_bounded');
        $snapshot = $registry->synchronize('app');
        $db->flushQueryLog();
        $db->enableQueryLog();
        for ($i = 0; $i < 100; $i++) {
            expect($registry->snapshot())->toBe($snapshot);
        }
        expect($db->getQueryLog())->toHaveCount(0);
        // Guarded APIs nest read transactions; a savepoint must not re-read
        // an ancestor's still-locked state for every permission or field.
        for ($i = 0; $i < 25; $i++) {
            $db->transaction(function () use ($db, $registry, $snapshot): void {
                expect($registry->snapshot())->toBe($snapshot);
                $db->transaction(fn () => expect($registry->snapshot())->toBe($snapshot));
            });
            expect($registry->snapshot())->toBe($snapshot);
        }
        expect($db->getQueryLog())->toHaveCount(0);
        $db->disableQueryLog();
        $db->table('shared.modules')->where('module_id', 'app.cache_bounded')->update(['enabled' => true]);
        $changed = $registry->snapshot();
        expect($changed->generation)->toBe($snapshot->generation)
            ->and($changed->modules['app.cache_bounded']->enabled)->toBeTrue()
            ->and($snapshot->modules['app.cache_bounded']->enabled)->toBeFalse();
    } finally {
        $db->disableQueryLog();
        $db->rollBack();
        $sources->remove();
    }
});

it('discards savepoint registry snapshots when a rolled-back generation is reused', function (): void {
    $sources = new ModuleSources;
    $db = DB::connection();
    $registry = new ModuleRegistry($db, new ModuleDiscovery($sources->root));
    $db->beginTransaction();
    try {
        $sources->add('app', 'cache_savepoint');
        $base = $registry->synchronize('app');
        $db->beginTransaction();
        $db->table('shared.modules')->where('module_id', 'app.cache_savepoint')->update(['enabled' => true]);
        $db->table('shared.module_registry_state')->where('id', 1)->increment('generation');
        $discarded = $registry->snapshot();
        expect($discarded->modules['app.cache_savepoint']->enabled)->toBeTrue();
        $db->rollBack();
        expect($registry->snapshot())->toEqual($base);
        $db->beginTransaction();
        $db->table('shared.modules')->where('module_id', 'app.cache_savepoint')->update(['source_present' => false]);
        $db->table('shared.module_registry_state')->where('id', 1)->increment('generation');
        $reused = $registry->snapshot();
        expect($reused->generation)->toBe($discarded->generation)
            ->and($reused->modules['app.cache_savepoint']->enabled)->toBeFalse()
            ->and($reused->modules['app.cache_savepoint']->sourcePresent)->toBeFalse();
        $db->commit();
        expect($registry->snapshot())->toEqual($reused);
    } finally {
        $db->rollBack(0);
        $sources->remove();
    }
});

it('invalidates registry reads even with database events suppressed and across root rollback reuse', function (): void {
    $sources = new ModuleSources;
    $db = DB::connection();
    $registry = new ModuleRegistry($db, new ModuleDiscovery($sources->root));
    $dispatcher = $db->getEventDispatcher();
    $db->unsetEventDispatcher();
    $db->beginTransaction();
    try {
        $sources->add('app', 'cache_suppressed');
        $registry->synchronize('app');
        $db->table('shared.modules')->where('module_id', 'app.cache_suppressed')->update(['enabled' => true]);
        $discarded = $registry->snapshot();
        expect($discarded->modules['app.cache_suppressed']->enabled)->toBeTrue();
        $db->rollBack();
        $db->beginTransaction();
        $reused = $registry->synchronize('app');
        expect($reused->generation)->toBe($discarded->generation)
            ->and($reused->modules['app.cache_suppressed']->enabled)->toBeFalse();
    } finally {
        $db->rollBack(0);
        if ($dispatcher !== null) {
            $db->setEventDispatcher($dispatcher);
        }
        $sources->remove();
    }
});

it('does not carry a registry snapshot across a reconnected PostgreSQL session', function (): void {
    $sources = new ModuleSources;
    $db = DB::connection();
    $registry = new ModuleRegistry($db, new ModuleDiscovery($sources->root));
    try {
        $before = $registry->snapshot();
        $db->disconnect();
        $after = $registry->snapshot();
        expect($after)->toEqual($before)->not->toBe($before);
    } finally {
        $sources->remove();
    }
});
