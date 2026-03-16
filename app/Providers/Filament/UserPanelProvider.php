<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Login;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use He4rt\Identity\Filament\User\Pages\Dashboard;
use He4rt\Identity\Filament\User\Pages\UserProfile;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class UserPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('user')
            ->path('app')
            ->login(Login::class)
            ->sidebarCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament/user/theme.css')
            ->tenant(Tenant::class, 'slug', 'ownedTenants')
            ->userMenuItems([
                Action::make('settings')
                    ->label(__('Profile'))
                    ->url(fn () => UserProfile::getUrl()),
            ])
            ->topbar(false)
            ->colors([
                'primary' => Color::Purple,
                ...Color::all(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
