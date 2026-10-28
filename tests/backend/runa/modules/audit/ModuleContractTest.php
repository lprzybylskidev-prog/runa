<?php

use Runa\Support\Modules\Development\ModuleContractSuite;
use Tests\PlatformTestCase;

uses(PlatformTestCase::class);

it('keeps Audit source within its owned public module contracts', function (): void {
    expect((new ModuleContractSuite(base_path(), true))->source('runa.audit'))->toBe([]);
});
