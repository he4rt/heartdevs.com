<?php

declare(strict_types=1);

namespace He4rt\Admin\Providers;

use App\Providers\Filament\FilamentPanel;
use Filament\Panel;
use He4rt\Admin\Plugins\AdminPanelPlugin;
use He4rt\Admin\Plugins\AppPanelPlugin;
use He4rt\Admin\Plugins\GuestPanelPlugin;
use He4rt\Admin\Plugins\PartnerPanelPlugin;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            match ($panel->currentPanel()) {
                FilamentPanel::Admin => $panel->plugin(new AdminPanelPlugin()),
                FilamentPanel::Partner => $panel->plugin(new PartnerPanelPlugin()),
                FilamentPanel::User => $panel->plugin(new AppPanelPlugin()),
                FilamentPanel::Guest => $panel->plugin(new GuestPanelPlugin()),
            };
        });
    }

    public function boot(): void
    {
    }
}
