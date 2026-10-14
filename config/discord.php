<?php

declare(strict_types=1);

use Discord\WebSockets\Intents;

return [
    'token' => env('DISCORD_TOKEN'),
    'intents' => Intents::getDefaultIntents() | Intents::GUILD_MEMBERS | Intents::MESSAGE_CONTENT,
    'options' => [
        'useTransportCompression' => false, // Disable zlib-stream
        'usePayloadCompression' => true,
    ],
];
