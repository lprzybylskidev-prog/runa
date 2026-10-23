<?php

return [
    // Server-side storage; operational Auth session metadata follows in its own flow.
    'driver' => 'file',
    'lifetime' => 120,
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
