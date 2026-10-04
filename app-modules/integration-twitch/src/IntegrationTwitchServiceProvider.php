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
use RuntimeException;

class IntegrationTwitchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TwitchOAuthConnector::class, fn (): TwitchOAuthConnector => new TwitchOAuthConnector(
            clientId: $this->twitchCredential('client_id'),
            clientSecret: $this->twitchCredential('client_secret'),
        ));

        $this->app->singleton(TwitchAppTokenService::class);

        $this->app->singleton(TwitchHelixConnector::class, fn (): TwitchHelixConnector => new TwitchHelixConnector(
            tokenService: $this->app->make(TwitchAppTokenService::class),
            clientId: $this->twitchCredential('client_id'),
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

    private function twitchCredential(string $key): string
    {
        $value = config('services.twitch.'.$key);

        throw_unless(
            is_string($value) && $value !== '',
            RuntimeException::class,
            'Twitch OAuth credentials are not configured (services.twitch.client_id / client_secret).',
        );

        return $value;
    }
}
