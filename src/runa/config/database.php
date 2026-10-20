<?php

return [
    'default' => env('DB_CONNECTION', 'pgsql'),
    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', 'postgres'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'runa'),
            'username' => env('DB_USERNAME', 'runa'),
            'password' => env('DB_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'shared',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
            'timezone' => 'UTC',
        ],
    ],
    'migrations' => ['table' => 'shared.application_migrations', 'update_date_on_publish' => true],
];
