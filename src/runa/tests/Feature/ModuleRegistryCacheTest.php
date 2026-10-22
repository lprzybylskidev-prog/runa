<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleRegistry;
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
        expect($registry->snapshot())->toBe($empty);
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
