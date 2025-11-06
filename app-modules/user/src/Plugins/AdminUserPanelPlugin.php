<?php

declare(strict_types=1);

namespace He4rt\User\Plugins;

use App\Enums\FilamentPanel;
use Filament\Contracts\Plugin;
use Filament\Panel;
use He4rt\Tenant\Filament\Admin\Resources\Tenants\TenantResource;
use He4rt\User\Filament\Admin\Resources\Users\UserResource;

class AdminUserPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return FilamentPanel::Admin->moduleName('user');
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            UserResource::class,
            TenantResource::class
        ]);
    }

    public function boot(Panel $panel): void
    {
    }
}
