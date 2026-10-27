<?php

use Illuminate\Support\Facades\DB;

it('returns the API health contract and preserves a valid request ID', function () {
    $this->getJson('/api/health', ['X-Request-ID' => 'health-test_123'])
        ->assertOk()->assertExactJson(['status' => 'ok'])
        ->assertHeader('X-Request-ID', 'health-test_123');
});

it('replaces an unsafe request ID and renders API errors as JSON', function () {
    $response = $this->getJson('/api/missing', ['X-Request-ID' => '<unsafe>']);
    $response->assertNotFound()->assertJsonStructure(['message']);
    expect($response->headers->get('X-Request-ID'))->toMatch('/^[0-9a-f-]{36}$/');
});

it('reports readiness without disclosing database details', function () {
    $this->getJson('/api/ready')->assertOk()->assertExactJson(['status' => 'ok'])
        ->assertHeader('Cache-Control', 'no-store, private');
});

it('reports unavailable readiness safely while liveness stays independent', function () {
    config(['database.connections.pgsql.host' => '127.0.0.1', 'database.connections.pgsql.port' => 1]);
    DB::purge('pgsql');
    $this->getJson('/api/ready')->assertStatus(503)->assertExactJson(['status' => 'unavailable']);
    $this->getJson('/api/health')->assertOk()->assertExactJson(['status' => 'ok']);
});
