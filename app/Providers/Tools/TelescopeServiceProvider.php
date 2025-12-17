<?php

declare(strict_types=1);

namespace App\Providers\Tools;

use He4rt\User\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();
        $this->hideSensitiveRequestDetails();

        Telescope::filter(fn (IncomingEntry $entry) => true);
    }

    public function boot(): void
    {
        if (! $this->canBoot()) {
            return;
        }

        parent::boot();
    }

    public function canBoot(): bool
    {
        return config()->boolean('telescope.enabled');
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', fn (User $user) => $user->isAdmin());
    }
}
