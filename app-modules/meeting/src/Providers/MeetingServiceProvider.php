<?php

declare(strict_types=1);

namespace He4rt\Meeting\Providers;

use App\Enums\FilamentPanel;
use Filament\Panel;
use He4rt\Meeting\AdminMeetingPanelPlugin;
use He4rt\Meeting\Contracts\MeetingRepository;
use He4rt\Meeting\Contracts\MeetingTypeRepository;
use He4rt\Meeting\Repositories\MeetingEloquentRepository;
use He4rt\Meeting\Repositories\MeetingTypeEloquentRepository;
use He4rt\Message\Plugins\AdminMessagePanelPlugin;
use Illuminate\Support\ServiceProvider;

class MeetingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MeetingRepository::class, MeetingEloquentRepository::class);
        $this->app->bind(MeetingTypeRepository::class, MeetingTypeEloquentRepository::class);

        Panel::configureUsing(function (Panel $panel): void {
            match ($panel->currentPanel()) {
                FilamentPanel::Admin => $panel->plugin(new AdminMessagePanelPlugin()),
                default => null,
            };
        });
    }

    public function boot(): void {}
}
