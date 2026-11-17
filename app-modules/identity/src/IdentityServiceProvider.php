<?php

declare(strict_types=1);

namespace He4rt\Identity;

use He4rt\Identity\Permissions\Commands\SyncPermissions\SyncPermissionsCommand;
use He4rt\Identity\Permissions\Permission;
use He4rt\Identity\Permissions\Role;
use He4rt\Identity\Teams\Team;
use He4rt\Identity\Users\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands(SyncPermissionsCommand::class);

        $this->mergeConfigFrom(__DIR__.'/../config/identity.php', 'identity');
        $this->mergeConfigFrom(__DIR__.'/../config/permissions.php', 'permission');
        $this->mergeConfigFrom(__DIR__.'/../config/rbac.php', 'rbac.identity');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/*');
        $this->loadTranslationsFrom(__DIR__.'/../lang/teams', 'teams');
        $this->loadTranslationsFrom(__DIR__.'/../lang/users', 'users');

        Relation::enforceMorphMap([
            'user' => User::class,
            'roles' => Role::class,
            'permissions' => Permission::class,
            'teams' => Team::class,
        ]);
    }
}
