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
use He4rt\PanelAdmin\Pages\Dashboard;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
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
            ->colors([
                'primary' => Color::Purple,
            ])
            ->tenant(Tenant::class, slugAttribute: 'slug')
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
            ->pages([
                Dashboard::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        foreach (config('panel-admin.modules', []) as $module) {
            $panel->discoverResourcesForPanel($module, FilamentPanel::Admin);
        }

        return $panel;
    }
}
