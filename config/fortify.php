<?php

declare(strict_types=1);

use App\Http\Middleware\SetUserLocale;
use He4rt\PanelApp\Auth\Http\Middleware\EnsureAuthenticationMethodIsEnabled;
use He4rt\PanelApp\Shared\Http\Middleware\HandleAppInertiaRequests;
use Laravel\Fortify\Features;

return [
    'guard' => 'web',

    'passwords' => 'users',

    'username' => 'email',

    'email' => 'email',

    'lowercase_usernames' => true,

    'home' => '/dashboard',

    'prefix' => '',

    'domain' => null,

    /*
     * The auth pages are panel-app pages, so its Inertia middleware picks their root view.
     */
    'middleware' => ['web', HandleAppInertiaRequests::class, EnsureAuthenticationMethodIsEnabled::class, SetUserLocale::class],

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
        'passkeys' => 'passkeys',
    ],

    'views' => true,

    'passkeys' => [
        'relying_party_id' => parse_url(config()->string('app.url'), PHP_URL_HOST),
        'allowed_origins' => [config('app.url')],
        'timeout' => 60_000,
    ],

    'features' => [
        Features::registration(),
        Features::resetPasswords(),
        Features::emailVerification(),
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]),
        Features::passkeys([
            'confirmPassword' => true,
        ]),
    ],
];
