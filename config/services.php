<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'discord' => [
        'client_id' => env('DISCORD_OAUTH_CLIENT_ID'),
        'client_secret' => env('DISCORD_OAUTH_CLIENT_SECRET'),
        'redirect_uri' => env('DISCORD_OAUTH_REDIRECT_URI', 'https://localhost:8000/auth/oauth/discord'),
        'scopes' => env('DISCORD_OAUTH_SCOPES', 'identify email'),
        'enabled' => env('DISCORD_OAUTH_ENABLED', true),
    ],

    'twitch' => [
        'client_id' => env('TWITCH_OAUTH_CLIENT_ID'),
        'client_secret' => env('TWITCH_OAUTH_CLIENT_SECRET'),
        'scopes' => [
            'admin' => env('TWITCH_OAUTH_SCOPES_ADMIN', 'user:read:email moderator:read:followers channel:read:subscriptions bits:read moderation:read channel:read:redemptions channel:read:polls channel:read:predictions channel:read:hype_train channel:read:goals channel:read:ads channel:bot'),
            'app' => env('TWITCH_OAUTH_SCOPES_APP', 'user:read:email'),
        ],
        'enabled' => env('TWITCH_OAUTH_ENABLED', true),
        'eventsub_secret' => env('TWITCH_EVENTSUB_SECRET', 'h34rt-tw1tch-3v3ntsub-s3cr3t-k3y'),
        'eventsub_callback' => env('TWITCH_EVENTSUB_CALLBACK'),
    ],

    'devto' => [
        'client_id' => env('DEVTO_OAUTH_CLIENT_ID'),
        'client_secret' => env('DEVTO_OAUTH_CLIENT_SECRET'),
        'redirect_uri' => env('DEVTO_OAUTH_REDIRECT_URI', 'https://localhost:8000/auth/oauth/devto'),
        'scopes' => env('DEVTO_OAUTH_SCOPES', 'public'),
        'enabled' => env('DEVTO_OAUTH_ENABLED', false),
    ],

    'github' => [
        'client_id' => env('GITHUB_OAUTH_CLIENT_ID'),
        'client_secret' => env('GITHUB_OAUTH_CLIENT_SECRET'),
        'scopes' => env('GITHUB_OAUTH_SCOPES', 'read:user user:email'),
        'enabled' => env('GITHUB_OAUTH_ENABLED', true),
        'api_token' => env('GITHUB_API_TOKEN'),
        'webhook_secret' => env('GITHUB_WEBHOOK_SECRET'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],
];
