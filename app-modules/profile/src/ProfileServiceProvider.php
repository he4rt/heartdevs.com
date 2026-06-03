<?php

declare(strict_types=1);

namespace He4rt\Profile;

use He4rt\Profile\Http\ProfileController;
use He4rt\Profile\Models\Profile;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class ProfileServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'profile');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'profile');

        Relation::morphMap([
            'profile' => Profile::class,
        ]);

        Route::get('/@{username}', [ProfileController::class, 'show'])
            ->name('profile.public');
    }
}
