<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Enums\FilamentPanel;
use App\Filament\Pages\Login;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\PanelAdmin\Http\Middleware\ApplyTenantScopes;
use He4rt\PanelAdmin\Pages\Dashboard;

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
            ->tenantMiddleware([
                ApplyTenantScopes::class,
            ], isPersistent: true)
            ->pages([
                Dashboard::class,
            ])
            ->viteTheme('app-modules/he4rt/resources/css/theme.css');

        foreach (config('panel-admin.modules', []) as $module) {
            $panel->discoverResourcesForPanel($module, FilamentPanel::Admin);
        }

        return $panel;
    }
}
