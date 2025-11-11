<?php

declare(strict_types=1);

namespace He4rt\Message\Providers;

use App\Enums\FilamentPanel;
use Filament\Panel;
use He4rt\Message\Contracts\MessageRepository;
use He4rt\Message\Contracts\VoiceRepository;
use He4rt\Message\Plugins\AdminMessagePanelPlugin;
use He4rt\Message\Repositories\MessageEloquentRepository;
use He4rt\Message\Repositories\VoiceEloquentRepository;
use Illuminate\Support\ServiceProvider;

final class MessageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MessageRepository::class, MessageEloquentRepository::class);
        $this->app->bind(VoiceRepository::class, VoiceEloquentRepository::class);

        Panel::configureUsing(function (Panel $panel): void {
            match ($panel->currentPanel()) {
                FilamentPanel::Admin => $panel->plugin(new AdminMessagePanelPlugin()),
                default => null,
            };
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
