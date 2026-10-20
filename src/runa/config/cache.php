<?php

return [
    'default' => 'file',
    'stores' => [
        'file' => ['driver' => 'file', 'path' => storage_path('framework/cache/data/application')],
        'runa_file' => ['driver' => 'file', 'path' => storage_path('framework/cache/data/runa')],
        'array' => ['driver' => 'array', 'serialize' => false],
    ],
    'prefix' => 'runa_cache_',
];
