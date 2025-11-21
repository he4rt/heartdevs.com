<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Http\Middleware\GuestTenantIdentifier;
use Filament\Facades\Filament;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Http\Middleware\IdentifyTenant;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use He4rt\Events\Filament\Shared\EventLogin;
use He4rt\Events\Filament\Shared\GuestSidebar;
use He4rt\Events\Filament\Shared\GuestTopbar;
use He4rt\Tenant\Models\Tenant;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\View\View;

class EventPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('event')
            ->path('event')
            ->tenant(Tenant::class, 'slug', 'ownedTenants')
            ->login(EventLogin::class)
            ->loginRouteSlug('{tenantSlug}/login')
            ->tenantDomain(app()->isLocal() ? null : sprintf('{tenant:slug}.%s', config('app.domain')))
            ->renderHook(PanelsRenderHook::TOPBAR_END, fn () => Blade::render(sprintf(<<<'BLADE'
               @guest
                    <x-he4rt::button href="%s">Fazer Login</x-he4rt::button>
               @endguest
            BLADE, Filament::getLoginUrl(['tenantSlug' => session()->get('tenant')]))
            ))
            ->tenantViteTheme()
            ->topbarLivewireComponent(GuestTopbar::class)
            ->sidebarLivewireComponent(GuestSidebar::class)
            ->discoverResources(in: app_path('Filament/Event/Resources'), for: 'App\Filament\Event\Resources')
            ->discoverPages(in: app_path('Filament/Event/Pages'), for: 'App\Filament\Event\Pages')
            ->brandLogo(fn (): View => view('he4rt::components.logo',
                [
                    'href' => '/event',
                    'path' => 'images/3pontos/logo.svg',
                ])
            )
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

    public function register(): void
    {
        Filament::registerPanel(
            fn (): Panel => $this->panel(Panel::make()),
        );

        $this->app->bind(IdentifyTenant::class, GuestTenantIdentifier::class);
    }
}
