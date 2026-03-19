<?php

declare(strict_types=1);

return [
    'org_slug' => env('DEVTO_ORG_SLUG', 'he4rt'),
    'api_base_url' => env('DEVTO_API_URL', 'https://dev.to/api'),
    'polling_interval_minutes' => env('DEVTO_POLLING_INTERVAL', 30),
];
