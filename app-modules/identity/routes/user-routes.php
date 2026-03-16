<?php

declare(strict_types=1);

use App\Http\Middleware\BotAuthentication;
use He4rt\Identity\User\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->middleware(['api', BotAuthentication::class])->group(function (): void {
    Route::prefix('users')->group(function (): void {
        Route::get('/', [UsersController::class, 'getUsers'])->name('get-users');
        Route::get('/profile/{value}', [UsersController::class, 'getProfile'])->name('users.profile');
        Route::put('/profile/{value}', [UsersController::class, 'putProfile'])
            ->name('users.profile.update');
        Route::get('/{id}', [UsersController::class, 'getUser'])->name('get-user');
    });
});
