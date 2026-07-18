<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch;

use He4rt\IntegrationTwitch\Console\LinkTwitchChannelCommand;
use He4rt\IntegrationTwitch\Console\SubscribeTwitchEventsCommand;
use He4rt\IntegrationTwitch\OAuth\TwitchAppTokenService;
use He4rt\IntegrationTwitch\OAuth\TwitchOAuthClient;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use He4rt\IntegrationTwitch\Transport\TwitchOAuthConnector;
use Illuminate\Support\ServiceProvider;

class IntegrationTwitchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TwitchOAuthConnector::class, fn (): TwitchOAuthConnector => new TwitchOAuthConnector(
            clientId: config()->string('services.twitch.client_id'),
            clientSecret: config()->string('services.twitch.client_secret')
        ));

        $this->app->singleton(TwitchAppTokenService::class);

        $this->app->singleton(TwitchHelixConnector::class, fn (): TwitchHelixConnector => new TwitchHelixConnector(
            tokenService: $this->app->make(TwitchAppTokenService::class),
            clientId: config()->string('services.twitch.client_id'),
        ));

        $this->app->singleton(TwitchOAuthClient::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LinkTwitchChannelCommand::class,
                SubscribeTwitchEventsCommand::class,
            ]);
        }
    }
}
