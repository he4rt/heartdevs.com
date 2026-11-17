<?php

declare(strict_types=1);

use He4rt\Identity\Users\Http\Controllers\ImpersonationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->post('impersonation/leave', [ImpersonationController::class, 'destroy'])
    ->name('identity.impersonation.leave');
