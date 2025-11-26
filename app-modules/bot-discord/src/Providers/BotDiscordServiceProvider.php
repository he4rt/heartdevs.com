<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Providers;

use Laracord\Laracord;
use Laracord\LaracordServiceProvider;

class BotDiscordServiceProvider extends LaracordServiceProvider
{
    public function bot(Laracord $bot): Laracord
    {
        return $bot
            ->discoverEvents(__DIR__.'/../Events', 'He4rt\\BotDiscord\\Events\\')
            ->discoverCommands(__DIR__.'/../Commands', 'He4rt\\BotDiscord\\Commands\\')
            ->discoverSlashCommands(__DIR__.'/../SlashCommands', 'He4rt\\BotDiscord\\SlashCommands\\')
            ->discoverTasks(__DIR__.'/../Tasks', 'He4rt\\BotDiscord\\Tasks\\');
    }
}
