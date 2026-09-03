<?php

declare(strict_types=1);

use He4rt\Profile\Http\Controllers\ProfileCardController;
use He4rt\Profile\Http\Controllers\PublicProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/@{username}', PublicProfileController::class)
    ->where('username', '[A-Za-z0-9_.-]+')
    ->middleware('throttle:public-profile')
    ->name('profile.public');

Route::get('/@{username}/card', ProfileCardController::class)
    ->where('username', '[A-Za-z0-9_.-]+')
    ->middleware(['web', 'throttle:profile-card'])
    ->name('profile.card');
