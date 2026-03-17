<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\Providers;

use He4rt\IntegrationTwitch\Client\TwitchBaseClient;
use He4rt\IntegrationTwitch\Contracts\TwitchService;
use He4rt\IntegrationTwitch\OAuth\Client\TwitchOAuthClient;
use He4rt\IntegrationTwitch\OAuth\Contracts\TwitchOAuthService;
use Illuminate\Support\ServiceProvider;

class IntegrationTwitchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TwitchService::class, TwitchBaseClient::class);
        $this->app->bind(TwitchOAuthService::class, TwitchOAuthClient::class);
    }

    public function boot(): void {}
}
