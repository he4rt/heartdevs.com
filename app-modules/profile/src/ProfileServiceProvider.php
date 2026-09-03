<?php

declare(strict_types=1);

namespace He4rt\Profile;

use He4rt\Profile\Models\Profile;
use He4rt\Profile\Support\PublicProfileCacheInvalidation;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

final class ProfileServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'profile');

        Relation::morphMap([
            'profile' => Profile::class,
        ]);

        PublicProfileCacheInvalidation::register();
    }
}
