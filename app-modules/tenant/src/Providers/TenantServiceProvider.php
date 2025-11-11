<?php

declare(strict_types=1);

namespace He4rt\Tenant\Providers;

use App\Enums\FilamentPanel;
use Filament\Panel;
use He4rt\Tenant\Plugins\AdminTenantPanelPlugin;
use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            match ($panel->currentPanel()) {
                FilamentPanel::Admin => $panel->plugin(new AdminTenantPanelPlugin()),
                default => null,
            };
        });
    }

    public function boot(): void {}
}
