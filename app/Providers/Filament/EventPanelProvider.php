<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use He4rt\Core\Filament\Pages\He4rtPage;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class EventPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('event')
            ->path('event')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->viteTheme('app-modules/he4rt/resources/css/3pontos/theme.css')
            ->discoverResources(in: app_path('Filament/Event/Resources'), for: 'App\Filament\Event\Resources')
            ->discoverPages(in: app_path('Filament/Event/Pages'), for: 'App\Filament\Event\Pages')
            ->pages([
                He4rtPage::class,
            ])
            ->topNavigation()
            ->discoverWidgets(in: app_path('Filament/Event/Widgets'), for: 'App\Filament\Event\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ]);
    }
}
