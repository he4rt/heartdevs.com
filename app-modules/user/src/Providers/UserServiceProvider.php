<?php

declare(strict_types=1);

namespace He4rt\User\Providers;

use App\Enums\FilamentPanel;
use Filament\Panel;
use He4rt\User\Contracts\UserRepository;
use He4rt\User\Plugins\AdminUserPanelPlugin;
use He4rt\User\Plugins\AppUserPanelPlugin;
use He4rt\User\Plugins\GuestUserPanelPlugin;
use He4rt\User\Plugins\PartnerUserPanelPlugin;
use He4rt\User\Repositories\UserEloquentRepository;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepository::class, UserEloquentRepository::class);

        Panel::configureUsing(function (Panel $panel): void {
            match ($panel->currentPanel()) {
                FilamentPanel::Admin => $panel->plugin(new AdminUserPanelPlugin()),
                FilamentPanel::Partner => $panel->plugin(new PartnerUserPanelPlugin()),
                FilamentPanel::User => $panel->plugin(new AppUserPanelPlugin()),
                FilamentPanel::Guest => $panel->plugin(new GuestUserPanelPlugin()),
            };
        });
    }

    public function boot(): void {}
}
