<?php

it('executes a Pest contribution through the native command', function (): void {
    expect(getenv('APP_ENV'))->toBe('testing');
});
