<?php

return [
    'default' => env('QUEUE_CONNECTION', 'database'),
    'connections' => [
        'sync' => ['driver' => 'sync'],
        'database' => [
            'driver' => 'database',
            'connection' => 'pgsql',
            'table' => 'shared.jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => true,
        ],
    ],
    'failed' => ['driver' => 'database-uuids', 'database' => 'pgsql', 'table' => 'shared.failed_jobs'],
];
