<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Home\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

final readonly class WelcomeController
{
    public function __invoke(): Response
    {
        return Inertia::render('home/welcome', [
            'dashboardUrl' => route('panel-app::dashboard', absolute: false),
            'loginUrl' => Route::has('login') ? route('login', absolute: false) : null,
            'registerUrl' => Route::has('register') ? route('register', absolute: false) : null,
        ]);
    }
}
