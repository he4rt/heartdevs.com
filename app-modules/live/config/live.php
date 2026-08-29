<?php

declare(strict_types=1);

return [
    'stream_key' => env('LIVE_STREAM_KEY', ''),
    'path' => 'live',
    'hls_url' => env('LIVE_HLS_URL', 'http://localhost:8888/live/index.m3u8'),
    'control_api_url' => env('LIVE_CONTROL_API_URL', 'http://localhost:9997'),
];
