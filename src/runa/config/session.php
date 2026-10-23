<?php

return [
    // Storage/cookie ceiling only. Auth enforces captured absolute and activity deadlines.
    'driver' => 'file',
    'lifetime' => 43200,
    'expire_on_close' => true,
    'encrypt' => true,
    'serialization' => 'json',
    'files' => storage_path('framework/sessions'),
    'lottery' => [2, 100],
    'cookie' => 'runa_session',
    'path' => '/',
    'domain' => null,
    'secure' => env('APP_ENV', 'production') === 'production',
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
];
