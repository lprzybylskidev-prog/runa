<?php

it('keeps English technical defaults and interpolates the Polish validation snapshot', function () {
    expect(app()->getLocale())->toBe('en');
    app()->setLocale('pl');
    expect(validator(['email' => ''], ['email' => 'required'])->errors()->first('email'))
        ->toBe('Pole email jest wymagane.');
});
