<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Providers;

use He4rt\BotDiscord\Commands\PingCommand;
use He4rt\BotDiscord\Events\GreetingsEvent;
use He4rt\BotDiscord\Events\MessageReceivedEvent;
use He4rt\BotDiscord\SlashCommands\IntroductionCommand;
use Laracord\Laracord;
use Laracord\LaracordServiceProvider;

class BotDiscordServiceProvider extends LaracordServiceProvider
{
    public function bot(Laracord $bot): Laracord
    {
        return $bot
            ->registerEvents([
                GreetingsEvent::class,
                MessageReceivedEvent::class,
            ])
            ->registerSlashCommand(IntroductionCommand::class)
            ->registerCommand(PingCommand::class);
    }
}
