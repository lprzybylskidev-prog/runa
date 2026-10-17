<?php

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
