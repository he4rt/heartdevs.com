<?php

declare(strict_types=1);

use Discord\WebSockets\Intents;

return [
    'token' => env('DISCORD_BOT_TOKEN'),
    'intents' => Intents::getDefaultIntents(),
    'options' => [
        'useTransportCompression' => false, // Disable zlib-stream
        'usePayloadCompression' => true,
    ],
];
