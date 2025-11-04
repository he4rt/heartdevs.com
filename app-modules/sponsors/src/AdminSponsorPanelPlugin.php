<?php

declare(strict_types=1);

namespace He4rt\Sponsors;

use Filament\Contracts\Plugin;
use Filament\Panel;
use He4rt\Sponsors\Filament\Resources\Sponsors\SponsorResource;

class AdminSponsorPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'admin-sponsor';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            SponsorResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
