<?php

declare(strict_types=1);

return [
    'client_id' => env('SLIDO_CLIENT_ID', '8d10c777-8ac6-4eb9-9d78-1e66cfc93b24'),
    'auth_url' => env('SLIDO_AUTH_URL', 'https://auth.slido.com'),
    'lookup_url' => env('SLIDO_LOOKUP_URL', 'https://app.sli.do/global/api'),
    'wall_url' => env('SLIDO_WALL_URL', 'https://wall.sli.do'),
    'stream_url' => env('SLIDO_STREAM_URL', 'wss://wall.sli.do'),
];
