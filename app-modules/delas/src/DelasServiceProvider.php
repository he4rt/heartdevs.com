<?php

declare(strict_types=1);

namespace He4rt\Delas;

use He4rt\Identity\Authorization\Enums\UserRole;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class DelasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/delas.php', 'delas');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'delas');

        $this->defineGates();
    }

    /**
     * Super admins passam em todos pelo `Gate::before` do identity.
     */
    private function defineGates(): void
    {
        Gate::define('moderate-delas', fn (User $user): bool => $user->hasAnyRole([
            UserRole::DelasModerator,
            UserRole::DelasLead,
        ]));

        Gate::define('lead-delas', fn (User $user): bool => $user->hasRole(UserRole::DelasLead));

        // Ninguém além do super admin, que passa pelo Gate::before.
        Gate::define('administer-delas', fn (User $user): bool => false);
    }
}
