<?php

namespace He4rt\Feedback\Plugins;

use App\Enums\FilamentPanel;
use Filament\Contracts\Plugin;
use Filament\Panel;
use He4rt\Feedback\Filament\Admin\Resources\Feedback\FeedbackResource;

class AdminFeedbackPanelPlugin implements Plugin
{

    public function getId(): string
    {
        return FilamentPanel::Admin->moduleName('feedback');
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            FeedbackResource::class
        ]);
    }

    public function boot(Panel $panel): void
    {
    }
}
