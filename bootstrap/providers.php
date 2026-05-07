<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\FilamentServiceProvider;
use App\Providers\RouteServiceProvider;

return [
    AppServiceProvider::class,
    EventServiceProvider::class,
    FilamentServiceProvider::class,
    AdminPanelProvider::class,
    RouteServiceProvider::class,
];
