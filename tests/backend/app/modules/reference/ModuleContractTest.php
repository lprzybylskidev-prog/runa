<?php

use Runa\Support\Modules\Development\ModuleContractSuite;
use Tests\TestCase;

uses(TestCase::class);

it('keeps the reference source within public module contracts', function (): void {
    expect((new ModuleContractSuite(base_path(), true))->source('app.reference'))->toBe([]);
});
