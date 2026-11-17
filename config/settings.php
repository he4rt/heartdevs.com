<?php

declare(strict_types=1);

use He4rt\Identity\Users\Auth\AuthenticationSettings;
use Spatie\LaravelSettings\SettingsCasts\DateTimeInterfaceCast;
use Spatie\LaravelSettings\SettingsCasts\DateTimeZoneCast;
use Spatie\LaravelSettings\SettingsRepositories\DatabaseSettingsRepository;

return [
    'settings' => [
        AuthenticationSettings::class,
    ],

    'setting_class_path' => app_path('Settings'),

    'migrations_paths' => [
        base_path('app-modules/identity/database/settings'),
    ],

    'default_repository' => 'database',

    'repositories' => [
        'database' => [
            'type' => DatabaseSettingsRepository::class,
            'model' => null,
            'table' => null,
            'connection' => null,
        ],
    ],

    'encoder' => null,
    'decoder' => null,

    'cache' => [
        'enabled' => (bool) env('SETTINGS_CACHE_ENABLED', default: false),
        'store' => null,
        'prefix' => null,
        'ttl' => null,
        'memo' => (bool) env('SETTINGS_CACHE_MEMO', default: false),
    ],

    'global_casts' => [
        DateTimeInterface::class => DateTimeInterfaceCast::class,
        DateTimeZone::class => DateTimeZoneCast::class,
    ],

    'auto_discover_settings' => [],

    'discovered_settings_cache_path' => base_path('bootstrap/cache'),
];
