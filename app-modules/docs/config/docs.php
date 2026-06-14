<?php

declare(strict_types=1);

return [
    'default_version' => '3.x',

    'cache' => [
        'enabled' => env('DOCS_CACHE_ENABLED', true),
        'ttl' => (int) env('DOCS_CACHE_TTL', 3600),
    ],
];
