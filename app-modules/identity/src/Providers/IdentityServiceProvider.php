<?php

declare(strict_types=1);

namespace He4rt\Identity\Providers;

use App\Enums\FilamentPanel;
use Filament\Panel;
use He4rt\Identity\Filament\Admin\Resources\Tenants\TenantResource;
use He4rt\Identity\Filament\Admin\Resources\Users\UserResource;
use He4rt\Identity\Filament\Shared\Widgets\UsersStatsOverview;
use He4rt\Identity\Filament\User\Pages\Dashboard;
use He4rt\Identity\Filament\User\Pages\UserProfile;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            match ($panel->currentPanel()) {
                FilamentPanel::Admin => $panel
                    ->resources([
                        UserResource::class,
                        TenantResource::class,
                    ])
                    ->widgets([
                        UsersStatsOverview::class,
                    ]),
                FilamentPanel::User => $panel
                    ->pages([
                        UserProfile::class,
                        Dashboard::class,
                    ]),
                default => null,
            };
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'identity');

        Relation::morphMap([
            'user' => User::class,
            'tenant' => Tenant::class,
        ]);
    }
}
