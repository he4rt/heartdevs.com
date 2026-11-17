<?php

declare(strict_types=1);

return [
    'domain' => env('APP_ADMIN_DOMAIN', ''),

    'path' => env('APP_ADMIN_PATH', 'admin'),

    'docs' => [
        'path' => base_path('docs/admin'),
        'fallback_locale' => 'en',
    ],
];
