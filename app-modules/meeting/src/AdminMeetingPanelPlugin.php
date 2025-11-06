<?php

declare(strict_types=1);

namespace He4rt\Meeting;

use Filament\Contracts\Plugin;
use Filament\Panel;
use He4rt\Meeting\Filament\Resources\Meetings\MeetingResource;

class AdminMeetingPanelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'admin-meeting';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            MeetingResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
