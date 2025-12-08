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

        $this->mergeConfigFrom(
            __DIR__.'/../../config/he4rt-bot-discord.php', 'he4rt-bot-discord');
    }

    public function bot(Laracord $bot): Laracord
    {
        return $bot
            ->discoverEvents(__DIR__.'/../Events', 'He4rt\BotDiscord\Events')
            ->discoverCommands(__DIR__.'/../Commands', 'He4rt\BotDiscord\Commands')
            ->discoverSlashCommands(__DIR__.'/../SlashCommands', 'He4rt\BotDiscord\SlashCommands')
            ->discoverTasks(__DIR__.'/../Tasks', 'He4rt\BotDiscord\Tasks');
    }
}
