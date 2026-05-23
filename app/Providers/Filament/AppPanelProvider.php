<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Enums\FilamentPanel;
use App\Filament\Pages\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\PanelApp\Pages\ProfilePage;
use He4rt\PanelApp\Pages\ThreadPage;
use He4rt\PanelApp\Pages\TimelinePage;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public FilamentPanel $panelId = FilamentPanel::App;

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id($this->panelId->value)
            ->path($this->panelId->value)
            ->login(Login::class)
            ->tenant(
                model: Tenant::class,
                slugAttribute: 'slug'
            )
            ->topbar(false)
            ->colors([
                'primary' => Color::Purple,
                'gray' => Color::Zinc,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\Filament\App\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\Filament\App\Pages')
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\Filament\App\Widgets')
            ->pages([
                TimelinePage::class,
                ThreadPage::class,
                ProfilePage::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
