<?php

declare(strict_types=1);

namespace He4rt\Tenant\Plugins;

use App\Enums\FilamentPanel;
use Filament\Contracts\Plugin;
use Filament\Panel;
use He4rt\Tenant\Filament\Admin\Resources\Tenants\TenantResource;
use He4rt\Tenant\Filament\Admin\Widgets\TenantsStatsOverview;

class AdminTenantPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return FilamentPanel::Admin->moduleName('tenant');
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            TenantResource::class,
        ]);

        $panel->widgets([
            TenantsStatsOverview::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
