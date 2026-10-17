<?php

use App\Support\Seeding\SeedContext;
use App\Support\Seeding\SeedGraph;
use Illuminate\Support\Facades\Artisan;
use Tests\Fixtures\BrokenSeed;
use Tests\Fixtures\CycleSeed;
use Tests\Fixtures\DemoSeed;
use Tests\Fixtures\FixtureSeed;
use Tests\Fixtures\ReferenceSeed;
use Tests\Fixtures\ScenarioSeed;

it('orders production before development and excludes manual scenarios from automatic Seeding', function () {
    $graph = new SeedGraph([ScenarioSeed::class, DemoSeed::class, ReferenceSeed::class]);
    expect($graph->plan('default', 'local'))->toBe([ReferenceSeed::class, DemoSeed::class]);
    expect($graph->plan('default', 'production'))->toBe([ReferenceSeed::class]);
    expect($graph->plan('production', 'testing'))->toBe([ReferenceSeed::class]);
    expect($graph->plan('occasional', 'testing', 'ScenarioSeed'))->toBe([ReferenceSeed::class, DemoSeed::class, ScenarioSeed::class]);
    expect(fn () => $graph->plan('development', 'production'))->toThrow(RuntimeException::class);
    expect(fn () => $graph->plan('occasional', 'production', 'ScenarioSeed'))->toThrow(RuntimeException::class);
});

it('validates the complete Seeding graph before any execution', function () {
    expect(fn () => (new SeedGraph([DemoSeed::class]))->plan('graph', 'testing'))->toThrow(RuntimeException::class, 'Missing');
    expect(fn () => (new SeedGraph([CycleSeed::class]))->plan('graph', 'testing'))->toThrow(RuntimeException::class, 'cycle');
    expect(fn () => (new SeedGraph([ReferenceSeed::class, DemoSeed::class, BrokenSeed::class]))->plan('graph', 'testing'))->toThrow(RuntimeException::class, 'Production seeders');
    expect(fn () => (new SeedGraph([ReferenceSeed::class]))->plan('occasional', 'testing', 'ReferenceSeed'))->toThrow(RuntimeException::class);
});

it('dispatches DatabaseSeeder and explicit Seeding commands with category isolation', function () {
    config()->set('seeding.register', [ReferenceSeed::class, DemoSeed::class, ScenarioSeed::class]);
    FixtureSeed::$executed = [];
    expect(Artisan::call('db:seed', ['--force' => true]))->toBe(0);
    expect(FixtureSeed::$executed)->toBe([ReferenceSeed::class, DemoSeed::class]);
    FixtureSeed::$executed = [];
    expect(Artisan::call('runa:seed', ['mode' => 'production']))->toBe(0);
    expect(FixtureSeed::$executed)->toBe([ReferenceSeed::class]);
    app()->instance('env', 'production');
    expect(fn () => app(DemoSeed::class)->run())->toThrow(RuntimeException::class, 'local or testing');
});

it('keeps independent deterministic Seeding random streams and validates per-group counts', function () {
    $first = new SeedContext(['seed' => 29, 'counts' => ['cases' => 3]]);
    $second = new SeedContext(['seed' => 29]);
    expect($first->random->getInt(0, 100000))->toBe($second->random->getInt(0, 100000));
    expect($first->count('cases'))->toBe(3);
    expect(fn () => new SeedContext(['random' => 'false']))->toThrow(RuntimeException::class);
    expect(fn () => (new SeedContext(['counts' => ['cases' => -1]]))->count('cases'))->toThrow(RuntimeException::class);
});

it('has an empty idempotent Seeding foundation without invented business data', function () {
    expect(Artisan::call('runa:seed'))->toBe(0);
    expect(Artisan::call('runa:seed'))->toBe(0);
    expect(SeedGraph::discover())->toBe([]);
});
