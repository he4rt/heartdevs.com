<?php

declare(strict_types=1);

namespace He4rt\Onboarding;

use He4rt\IntegrationGithub\Events\GithubPullRequestApproved;
use He4rt\Onboarding\Contracts\OnboardingCompletionGate;
use He4rt\Onboarding\Listeners\CompleteGitChallengeOnPullRequestApproval;
use He4rt\Onboarding\Services\EloquentOnboardingCompletionGate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class OnboardingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OnboardingCompletionGate::class, EloquentOnboardingCompletionGate::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Event::listen(GithubPullRequestApproved::class, [CompleteGitChallengeOnPullRequestApproval::class, 'handle']);
    }
}
