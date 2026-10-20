<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

uses(DatabaseTransactions::class);

it('writes technical queue state inside a rolled back worker transaction', function () {
    $id = DB::table('shared.jobs')->insertGetId([
        'queue' => 'test-smoke', 'payload' => '{}', 'attempts' => 0,
        'reserved_at' => null, 'available_at' => 1, 'created_at' => 1,
    ]);
    expect($id)->toBeGreaterThan(0);
    expect(DB::table('shared.jobs')->where('id', $id)->value('queue'))->toBe('test-smoke');
});
