<?php

declare(strict_types=1);

use He4rt\Docs\Discovery\Enums\DocumentType;
use He4rt\Docs\DocsController;
use Illuminate\Support\Facades\Route;

$sections = implode('|', array_map(
    static fn (DocumentType $type): string => $type->value,
    DocumentType::cases(),
));

Route::get('docs', [DocsController::class, 'index'])->name('docs.index');

// The section is constrained to known document types so Scramble's
// `docs/3.x/api` (and any other prefix) falls through to its own route.
Route::get('docs/{section}/{path?}', [DocsController::class, 'show'])
    ->where('section', $sections)
    ->where('path', '.*')
    ->name('docs.show');
