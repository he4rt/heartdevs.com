<?php

declare(strict_types=1);

namespace He4rt\Badge;

use Filament\Contracts\Plugin;
use Filament\Panel;
use He4rt\Badge\Filament\Resources\Badges\BadgeResource;

final class AdminBadgePanelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'admin-badge';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            BadgeResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
