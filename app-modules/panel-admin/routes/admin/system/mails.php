<?php

declare(strict_types=1);

use He4rt\PanelAdmin\Features\System\Mails\Http\Controllers\MailAttachmentController;
use He4rt\PanelAdmin\Features\System\Mails\Http\Controllers\MailController;
use He4rt\PanelAdmin\Features\System\Mails\Http\Controllers\MailPreviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('/mails')
    ->name('mails.')
    ->whereNumber(['mail', 'attachment'])
    ->group(function (): void {
        Route::get('/', [MailController::class, 'index'])->name('index');
        Route::get('/{mail}', [MailController::class, 'show'])->name('show');
        Route::get('/{mail}/preview', [MailPreviewController::class, 'show'])->name('preview');
        Route::get('/{mail}/attachments/{attachment}', [MailAttachmentController::class, 'show'])->scopeBindings()->name('attachment');
    });
