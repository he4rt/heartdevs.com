<?php

declare(strict_types=1);

return [
    'token' => env('DISCORD_TOKEN'),
    'options' => [
        'useTransportCompression' => false, // Disable zlib-stream
        'usePayloadCompression' => true,
    ],
];
