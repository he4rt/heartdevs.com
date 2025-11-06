<?php

declare(strict_types=1);

namespace He4rt\Feedback\Providers;

use App\Enums\FilamentPanel;
use Filament\Panel;
use He4rt\Feedback\Contracts\FeedbackRepository;
use He4rt\Feedback\Plugins\AdminFeedbackPanelPlugin;
use He4rt\Feedback\Repositories\FeedbackEloquentRepository;
use Illuminate\Support\ServiceProvider;

class FeedbackServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FeedbackRepository::class, FeedbackEloquentRepository::class);

        Panel::configureUsing(function (Panel $panel): void {
            match ($panel->currentPanel()) {
                FilamentPanel::Admin => $panel->plugin(new AdminFeedbackPanelPlugin()),
                default => null,
            };
        });
    }

    public function boot(): void {}
}
