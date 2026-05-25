<?php

declare(strict_types=1);

namespace He4rt\IntegrationGithub;

use He4rt\IntegrationGithub\Transport\GitHubApiConnector;
use He4rt\IntegrationGithub\Transport\GitHubOAuthConnector;
use Illuminate\Support\ServiceProvider;

class IntegrationGithubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GitHubOAuthConnector::class, fn () => new GitHubOAuthConnector(
            clientId: config('services.github.client_id'),
            clientSecret: config('services.github.client_secret'),
        ));

        $this->app->singleton(GitHubApiConnector::class, fn () => new GitHubApiConnector());
    }

    public function boot(): void {}
}
