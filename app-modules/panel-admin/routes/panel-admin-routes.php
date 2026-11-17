<?php

declare(strict_types=1);

use App\Http\Middleware\RequireTwoFactorSetup;
use App\Http\Middleware\SetUserLocale;
use He4rt\PanelAdmin\Auth\Http\Controllers\LoginController;
use He4rt\PanelAdmin\Shared\Http\Middleware\EnsureSuperAdmin;
use He4rt\PanelAdmin\Shared\Http\Middleware\HandleAdminInertiaRequests;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;

Route::group(collect([
    'domain' => config()->string('panel-admin.domain'),
    'prefix' => mb_trim(config()->string('panel-admin.path'), '/'),
    'as' => 'panel-admin::',
    'middleware' => ['web', HandleAdminInertiaRequests::class],
])->filter()->all(), function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::middleware([EnsureSuperAdmin::class, AuthenticateSession::class, RequireTwoFactorSetup::class, SetUserLocale::class])->group(function (): void {
        foreach (glob(__DIR__.'/admin/{,*/}*.php', GLOB_BRACE) ?: [] as $routes) {
            require $routes;
        }
    });
});
