<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\DiscordServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\EventPanelProvider;
use App\Providers\Filament\GuestPanelProvider;
use App\Providers\Filament\PartnerPanelProvider;
use App\Providers\Filament\UserPanelProvider;
use App\Providers\FilamentServiceProvider;
use App\Providers\RouteServiceProvider;

return [
    AppServiceProvider::class,
    EventServiceProvider::class,
    FilamentServiceProvider::class,
    AdminPanelProvider::class,
    EventPanelProvider::class,
    GuestPanelProvider::class,
    PartnerPanelProvider::class,
    UserPanelProvider::class,
    RouteServiceProvider::class,
    DiscordServiceProvider::class,
];
