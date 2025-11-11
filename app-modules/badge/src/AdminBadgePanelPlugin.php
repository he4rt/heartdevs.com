<?php

declare(strict_types=1);

namespace He4rt\Badge;

use App\Enums\FilamentPanel;
use Filament\Contracts\Plugin;
use Filament\Panel;
use He4rt\Badge\Filament\Resources\Badges\BadgeResource;

final class AdminBadgePanelPlugin implements Plugin
{
    public function getId(): string
    {
        return FilamentPanel::Admin->moduleName('badge');
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            BadgeResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
