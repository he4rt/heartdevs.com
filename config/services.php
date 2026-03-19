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
        'redirect_uri' => env('TWITCH_OAUTH_REDIRECT_URI', 'https://localhost:8000/auth/oauth/twitch'),
        'scopes' => env('TWITCH_OAUTH_SCOPES', ''),
        'enabled' => env('TWITCH_OAUTH_ENABLED', true),
    ],

    'devto' => [
        'client_id' => env('DEVTO_OAUTH_CLIENT_ID'),
        'client_secret' => env('DEVTO_OAUTH_CLIENT_SECRET'),
        'redirect_uri' => env('DEVTO_OAUTH_REDIRECT_URI', 'https://localhost:8000/auth/oauth/devto'),
        'scopes' => env('DEVTO_OAUTH_SCOPES', 'public'),
        'enabled' => env('DEVTO_OAUTH_ENABLED', false),
    ],

];
