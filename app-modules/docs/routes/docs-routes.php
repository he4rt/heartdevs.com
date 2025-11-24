<?php

declare(strict_types=1);

use He4rt\Docs\DocsController;
use Illuminate\Support\Facades\Route;

if (! defined('DEFAULT_VERSION')) {
    define('DEFAULT_VERSION', '3.x');
}

Route::get('docs', [DocsController::class, 'showRootPage']);
Route::get('docs/{version}/index.json', [DocsController::class, 'index']);
Route::get('docs/{version}/sidebar.json', [DocsController::class, 'sidebar']);
Route::get('docs/{version}/{page?}', [DocsController::class, 'show'])->name('docs.version');
