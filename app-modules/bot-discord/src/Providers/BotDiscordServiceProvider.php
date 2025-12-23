<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Providers;

use Laracord\Laracord;
use Laracord\LaracordServiceProvider;

class BotDiscordServiceProvider extends LaracordServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(__DIR__.'/../../config/bot-discord.php', 'bot-discord');
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/bot-discord.php' => config_path('bot-discord.php'),
            ], 'bot-discord-config');
        }
    }

    public function bot(Laracord $bot): Laracord
    {
        return $bot
            ->disableHttpServer()
            ->discoverEvents(__DIR__.'/../Events', 'He4rt\BotDiscord\Events')
            ->discoverCommands(__DIR__.'/../Commands', 'He4rt\BotDiscord\Commands')
            ->discoverSlashCommands(__DIR__.'/../SlashCommands', 'He4rt\BotDiscord\SlashCommands')
            ->discoverTasks(__DIR__.'/../Tasks', 'He4rt\BotDiscord\Tasks');
    }
}
