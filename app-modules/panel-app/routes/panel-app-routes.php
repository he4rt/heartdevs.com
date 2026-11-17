<?php

declare(strict_types=1);

use App\Http\Middleware\RequireTwoFactorSetup;
use App\Http\Middleware\SetUserLocale;
use He4rt\PanelApp\Shared\Http\Middleware\HandleAppInertiaRequests;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', HandleAppInertiaRequests::class, RequireTwoFactorSetup::class, SetUserLocale::class])
    ->as('panel-app::')
    ->group(function (): void {
        foreach (glob(__DIR__.'/app/{,*/}*.php', GLOB_BRACE) ?: [] as $routes) {
            require $routes;
        }
    });
