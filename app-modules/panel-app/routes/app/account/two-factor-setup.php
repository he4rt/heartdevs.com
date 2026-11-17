<?php

declare(strict_types=1);

use App\Http\Middleware\RequireTwoFactorSetup;
use He4rt\PanelApp\Features\Account\TwoFactorSetup\Http\Controllers\TwoFactorSetupController;
use Illuminate\Support\Facades\Route;

Route::get('/setup/two-factor', TwoFactorSetupController::class)
    ->middleware(['auth', 'password.confirm'])
    ->withoutMiddleware(RequireTwoFactorSetup::class)
    ->name('two-factor.setup');
