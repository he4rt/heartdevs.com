<?php

declare(strict_types=1);

use He4rt\PanelOverlays\Http\Controllers\ShowOverlayController;
use He4rt\PanelOverlays\Http\Middleware\HandleOverlayInertiaRequests;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', HandleOverlayInertiaRequests::class])
    ->get('overlay/{token}/{scene}', ShowOverlayController::class)
    ->where('token', '[a-z0-9]{32}')
    ->name('overlays.show');
