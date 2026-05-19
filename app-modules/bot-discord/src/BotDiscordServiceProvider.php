<?php

declare(strict_types=1);

namespace He4rt\BotDiscord;

use He4rt\BotDiscord\Events\RawGatewayEvent;
use He4rt\BotDiscord\Listeners\AutoExecuteAction;
use He4rt\BotDiscord\Listeners\NotifyModerationChannel;
use He4rt\BotDiscord\Moderation\DiscordModerationAdapter;
use He4rt\Moderation\Cases\Events\CaseQueued;
use He4rt\Moderation\Cases\Events\CaseReadyForEnforcement;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Platform\PlatformRegistry;
use Illuminate\Support\Facades\Event;
use Laracord\Bot\Hook;
use Laracord\Laracord;
use Laracord\LaracordServiceProvider;

class BotDiscordServiceProvider extends LaracordServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/bot-discord.php', 'bot-discord');

        $this->app->singleton(DiscordModerationAdapter::class);

        // Register Discord adapter in the moderation PlatformRegistry for O(1) lookup during enforcement.
        $this->app->afterResolving(PlatformRegistry::class, function (PlatformRegistry $registry): void {
            $registry->register(Platform::Discord, DiscordModerationAdapter::class);
        });

        parent::register();
    }

    public function boot(): void
    {
        parent::boot();

        // Domain event listeners: moderation module emits events, bot-discord reacts with Discord-specific behavior.
        Event::listen(CaseQueued::class, [NotifyModerationChannel::class, 'handle']);
        Event::listen(CaseReadyForEnforcement::class, [AutoExecuteAction::class, 'handle']);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/bot-discord.php' => config_path('bot-discord.php'),
            ], 'bot-discord-config');
        }
    }

    public function bot(Laracord $bot): Laracord
    {
        return $bot
            ->disableHttpServer()
            ->discoverEvents(__DIR__.'/Events', 'He4rt\BotDiscord\Events')
            ->discoverCommands(__DIR__.'/Commands', 'He4rt\BotDiscord\Commands')
            ->discoverSlashCommands(__DIR__.'/SlashCommands', 'He4rt\BotDiscord\SlashCommands')
            ->discoverTasks(__DIR__.'/Tasks', 'He4rt\BotDiscord\Tasks')
            ->registerHook(Hook::AFTER_BOOT, function () use ($bot): void {
                $bot->discord()->on('raw', function (object $payload): void {
                    resolve(RawGatewayEvent::class)->handle($payload);
                });
            });
    }
}
