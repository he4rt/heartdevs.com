<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord;

use He4rt\IntegrationDiscord\ETL\Console\ImportDiscordMessagesCommand;
use He4rt\IntegrationDiscord\ETL\Console\ImportDiscordProfilesCommand;
use He4rt\IntegrationDiscord\ETL\Console\MergeDuplicateDiscordProfilesCommand;
use Illuminate\Support\ServiceProvider;

class IntegrationDiscordServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportDiscordProfilesCommand::class,
                ImportDiscordMessagesCommand::class,
                MergeDuplicateDiscordProfilesCommand::class,
            ]);
        }
    }
}
