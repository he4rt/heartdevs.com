<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch;

use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityConnected;
use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityDisconnected;
use He4rt\IntegrationTwitch\Console\LinkTwitchChannelCommand;
use He4rt\IntegrationTwitch\Console\SubscribeTwitchEventsCommand;
use He4rt\IntegrationTwitch\ETL\Listeners\ProjectTwitchEventToStreaming;
use He4rt\IntegrationTwitch\Events\TwitchEventReceived;
use He4rt\IntegrationTwitch\Listeners\InferChatReaderFromGrantedScopes;
use He4rt\IntegrationTwitch\Listeners\SyncStreamerSubscriptionsOnChange;
use He4rt\IntegrationTwitch\OAuth\TwitchAppTokenService;
use He4rt\IntegrationTwitch\OAuth\TwitchOAuthClient;
use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;
use He4rt\IntegrationTwitch\Transport\TwitchOAuthConnector;
use He4rt\Streaming\Streamer\Events\StreamerActivated;
use He4rt\Streaming\Streamer\Events\StreamerDisabled;
use He4rt\Streaming\Streamer\Events\StreamerSourceRegistered;
use He4rt\Streaming\Streamer\Events\StreamerSourceUpdated;
use Illuminate\Support\Facades\Event;
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
        Event::listen(TwitchEventReceived::class, ProjectTwitchEventToStreaming::class);
        Event::listen(StreamerSourceRegistered::class, [InferChatReaderFromGrantedScopes::class, 'handle']);
        Event::listen([
            ExternalIdentityConnected::class,
            ExternalIdentityDisconnected::class,
            StreamerActivated::class,
            StreamerDisabled::class,
            StreamerSourceRegistered::class,
            StreamerSourceUpdated::class,
        ], SyncStreamerSubscriptionsOnChange::class);

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
