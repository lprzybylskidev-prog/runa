<?php

use App\Modules\Reference\Contracts\Ping;
use Illuminate\Support\Facades\DB;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\ModuleHttp;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Development\ModuleContractSuite;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleHealth;
use Runa\Support\Modules\ModuleManifest;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\ModuleTransitions;
use Runa\Support\Modules\Settings\ModuleSettings;
use Runa\Support\Modules\Storage\ModuleStorage;
use Runa\Support\Modules\Updates\ModuleUpdates;
use Runa\Support\Modules\Work\ModuleWork;

it('installs the real reference while disabled and guards its cached public API after lifecycle changes', function (): void {
    $registry = app(ModuleRegistry::class);
    $before = array_keys($registry->snapshot()->modules);
    expect($before)->not->toContain('app.reference');
    try {
        $updates = app(ModuleUpdates::class);
        $updates->apply('app', str_repeat('e', 40), static function (): void {});
        $record = $registry->snapshot()->modules['app.reference'];
        expect($record->enabled)->toBeFalse()->and($record->installedVersion)->toBe('0.1.0')
            ->and(DB::table('app_reference.samples')->count())->toBe(1)
            ->and(DB::table('app_reference.samples')->value('public_id'))->toMatch('/^[a-f0-9-]{14}7[a-f0-9-]+$/')
            ->and((new ModuleContractSuite(base_path(), true))->runtime($record))->toBe([]);
        $updates->apply('app', str_repeat('e', 40), static function (): void {});
        expect(DB::table('app_reference.samples')->count())->toBe(1);
        $transitions = app(ModuleTransitions::class);
        expect(app(ModuleHealth::class)->diagnose('app.reference')['blocked'])->toBeFalse();
        $preview = $transitions->preview('enable', 'app.reference');
        expect($preview->ready())->toBeTrue();
        expect($transitions->apply($preview)->status)->toBe('succeeded');
        app(ModuleHttp::class)->register();
        $this->getJson('/api/app/reference/ping')->assertOk()->assertJson(['message' => 'reference']);
        $api = app(ModuleApis::class)->resolve('app.reference', Ping::class, '1.0.0');
        expect($api->read()->message)->toBe('reference');
        $settings = app(ModuleSettings::class);
        $state = $settings->read('app.reference');
        expect($settings->update('app.reference', ['label' => 'Updated'], $state['revision'], $state['generation'])->status)->toBe('succeeded');
        expect($api->read()->message)->toBe('Updated');
        config(['queue.default' => 'database', 'queue.connections.database.queue' => 'reference-acceptance']);
        $work = app(ModuleWork::class);
        $workId = $work->dispatch('app.reference', 'reference.work', []);
        $work->execute($workId);
        expect($work->status($workId)->status)->toBe('succeeded');
        $eventWork = DB::table('shared.module_work')->where('module_id', DB::table('shared.modules')->where('module_id', 'app.reference')->value('id'))->where('operation', 'reference.event')->value('public_id');
        expect($eventWork)->toBeString();
        $work->execute($eventWork);
        expect($work->status($eventWork)->status)->toBe('succeeded');
        expect(app(ModuleStorage::class)->disk('app.reference')->get('last-observation.txt'))->toBe('Reference work completed.');
        expect($transitions->apply($transitions->preview('disable', 'app.reference'))->status)->toBe('succeeded');
        expect(fn () => $api->read())->toThrow(OperationRejected::class);
        $this->getJson('/api/app/reference/ping')->assertStatus(503);
        expect(DB::table('app_reference.samples')->count())->toBe(1);
    } finally {
        // Only newly registered fixture identities in the guarded test database.
        $ids = DB::table('shared.modules')->whereNotIn('module_id', $before)->pluck('id');
        DB::table('shared.jobs')->where('queue', 'reference-acceptance')->delete();
        DB::table('shared.module_work')->where('module_id', DB::table('shared.modules')->where('module_id', 'app.reference')->value('id'))->delete();
        app(ModuleStorage::class)->disk('app.reference')->delete('last-observation.txt');
        DB::table('shared.module_update_steps')->whereIn('module_id', $ids)->delete();
        DB::table('shared.module_transitions')->where('target', 'app.reference')->delete();
        DB::table('shared.modules')->whereIn('id', $ids)->delete();
        DB::statement('DROP TABLE IF EXISTS app_reference.samples RESTRICT');
        DB::statement('DROP SCHEMA IF EXISTS app_reference RESTRICT');
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    }
});

it('refuses reference discovery in production even when source was accidentally copied', function (): void {
    expect(fn () => (new ModuleDiscovery(base_path()))->discover('app'))->toThrow(InvalidArgumentException::class);
    expect(fn () => ModuleManifest::load(base_path('app/modules/reference/module.json'), 'app', 'reference'))->toThrow(InvalidArgumentException::class);
    expect(file_get_contents(dirname(base_path()).'/.dockerignore'))->toContain('src/app/modules/reference');
});
