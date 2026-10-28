<?php

use Monolog\Handler\StreamHandler;
use Runa\Logging\ConfigureStructuredLogging;

return [
    'default' => 'stack',
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['stderr', 'files'],
            'ignore_exceptions' => false,
        ],
        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'info'),
            'handler' => StreamHandler::class,
            'with' => ['stream' => 'php://stderr'],
            'tap' => [ConfigureStructuredLogging::class],
        ],
        'files' => [
            'driver' => 'daily',
            'level' => env('LOG_LEVEL', 'info'),
            'path' => storage_path('logs/runa.log'),
            'days' => 14,
            'permission' => 0640,
            'locking' => true,
            'replace_placeholders' => false,
            'tap' => [ConfigureStructuredLogging::class],
        ],
    ],
];
