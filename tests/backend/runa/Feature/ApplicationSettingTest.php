<?php

use Runa\Contracts\Settings\ApplicationLogo;
use Runa\Contracts\Settings\ApplicationSetting;

it('refuses unsafe source logo URLs and invalid accessible names', function (string $src): void {
    expect(fn () => new ApplicationLogo('atlas', $src, 'Atlas'))->toThrow(InvalidArgumentException::class);
})->with(['//remote.test/logo.png', 'https://remote.test/logo.png', '/a/../logo.png', '/%2e%2e/logo.png', '/logo.png?token=secret', '/logo.svg#script', 'data:image/png;base64,x', '/logo.html', '/logo.png/extra', '/a\\logo.png']);

it('preserves strict safe scalar types and nullable source fallback', function (): void {
    $integer = new ApplicationSetting('business.limit', 'integer', 5, 'Business limit', 'app.orders.read', 'app.orders.manage');
    expect($integer->accepts(10))->toBeTrue()->and($integer->accepts('10'))->toBeFalse()
        ->and($integer->accepts(null))->toBeTrue()->and($integer->accepts(9007199254740992))->toBeFalse();
    expect(fn () => new ApplicationSetting('business.label', 'string', 'Too long', 'Label', 'app.orders.read', 'app.orders.manage', 2))->toThrow(InvalidArgumentException::class);
    expect(fn () => new ApplicationSetting('business.flag', 'boolean', 'true', 'Flag', 'app.orders.read', 'app.orders.manage'))->toThrow(InvalidArgumentException::class);
});
