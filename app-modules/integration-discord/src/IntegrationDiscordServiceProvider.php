<?php

declare(strict_types=1);

namespace He4rt\IntegrationDiscord;

use He4rt\IntegrationDiscord\ETL\Console\ImportDiscordMessagesCommand;
use He4rt\IntegrationDiscord\ETL\Console\ImportDiscordProfilesCommand;
use He4rt\IntegrationDiscord\ETL\Console\MergeDuplicateDiscordProfilesCommand;
use He4rt\IntegrationDiscord\Transport\DiscordConnector;
use He4rt\IntegrationDiscord\Transport\DiscordOAuthConnector;
use Illuminate\Support\ServiceProvider;

class IntegrationDiscordServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DiscordConnector::class, fn (): DiscordConnector => new DiscordConnector(
            botToken: config()->string('discord.token'),
        ));

        $this->app->singleton(DiscordOAuthConnector::class, fn (): DiscordOAuthConnector => new DiscordOAuthConnector(
            clientId: config()->string('services.discord.client_id'),
            clientSecret: config()->string('services.discord.client_secret'),
            redirectUri: config()->string('services.discord.redirect_uri'),
        ));
    }

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
