<?php

return [
    'default' => env('MAIL_MAILER', 'smtp'),
    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME', 'smtp'),
            'host' => env('MAIL_HOST', 'mailpit'),
            'port' => env('MAIL_PORT', 1025),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => 10,
            'require_tls' => env('MAIL_REQUIRE_TLS', false),
        ],
        'array' => ['transport' => 'array'],
    ],
    'markdown' => [
        'theme' => 'runa',
        'paths' => [app_path('resources/views/mail'), base_path('runa/resources/views/mail')],
    ],
    'from' => ['address' => env('MAIL_FROM_ADDRESS', 'runa@example.com'), 'name' => 'Runa'],
];
