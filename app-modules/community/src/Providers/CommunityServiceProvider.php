<?php

declare(strict_types=1);

namespace He4rt\Community\Providers;

use He4rt\Community\Feedback\Filament\Admin\Resources\Feedback\FeedbackResource;
use He4rt\Community\Meeting\Filament\Resources\Meetings\MeetingResource;
use He4rt\Community\Meeting\Filament\Resources\MeetingTypes\MeetingTypeResource;
use Illuminate\Support\ServiceProvider;

class CommunityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
