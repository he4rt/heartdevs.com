<?php

declare(strict_types=1);

return [
    'token' => env('HE4RT_DISCORD_BOT_KEY'),
    'options' => [
        'useTransportCompression' => false, // Disable zlib-stream
        'usePayloadCompression' => true,
    ],
];
