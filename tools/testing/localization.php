<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if ($app->getLocale() !== 'en') {
    throw new RuntimeException('Technical backend locale must remain English.');
}
try {
    $app->setLocale('pl');
    $validator = Illuminate\Support\Facades\Validator::make([], ['email' => 'required']);
    if ($validator->errors()->first('email') !== 'Pole email jest wymagane.') {
        throw new RuntimeException('Polish validation did not load through the native Laravel translator.');
    }
    $validator = Illuminate\Support\Facades\Validator::make(['items' => [1]], ['items' => 'array|min:3']);
    $message = $validator->errors()->first('items');
    if (! str_contains($message, '3') || str_contains($message, ':min') || ! str_contains($message, 'items')) {
        throw new RuntimeException('Nested Polish validation placeholders did not resolve.');
    }
} finally {
    $app->setLocale('en');
}
fwrite(STDOUT, "PASS: native Polish validation and placeholder substitution; technical locale remains English.\n");
