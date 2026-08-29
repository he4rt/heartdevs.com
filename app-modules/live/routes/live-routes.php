<?php

declare(strict_types=1);

use He4rt\Live\IngestAuthController;
use Illuminate\Support\Facades\Route;

Route::post('live/ingest/auth', IngestAuthController::class)->name('live.ingest-auth');
