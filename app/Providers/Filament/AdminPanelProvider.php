<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\PanelAdmin\Http\Middleware\ApplyTenantScopes;
use He4rt\PanelAdmin\Pages\Dashboard;
use He4rt\PanelAdmin\Tenant\EditTenantProfilePage;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {

        $panel
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->brandName('He4rt Admin')
            ->brandLogo(fn () => view('he4rt::components.base.logo'))
            ->brandLogoHeight('2.5rem')
            ->colors(function (): array {
                $colors = Color::all();

                unset($colors['gray']);

                return [
                    'primary' => Color::Purple,
                    'gray' => Color::Zinc,
                    ...$colors,
                ];
            })
            ->sidebarCollapsibleOnDesktop()
            ->tenantMenu(false)
            ->tenant(Tenant::class, slugAttribute: 'slug')
            ->tenantProfile(EditTenantProfilePage::class)
            ->viteTheme('resources/css/filament/admin/theme.css')
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
            ->pages([
                Dashboard::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->tenantMiddleware([
                ApplyTenantScopes::class,
            ]);

        return $panel;
    }
}
