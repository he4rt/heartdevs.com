<?php

declare(strict_types=1);

namespace He4rt\Ingestion\Providers;

use He4rt\Ingestion\Console\Commands\BackfillPostgresToTimescaleCommand;
use He4rt\Ingestion\Listeners\ProcessRawDiscordMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class IngestionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            BackfillPostgresToTimescaleCommand::class,
        ]);
    }

    public function boot(): void
    {
        Event::listen('discord.message.received', ProcessRawDiscordMessage::class);
    }
}
