<?php

declare(strict_types=1);

use He4rt\Portal\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortalController::class])->name('homepage');
