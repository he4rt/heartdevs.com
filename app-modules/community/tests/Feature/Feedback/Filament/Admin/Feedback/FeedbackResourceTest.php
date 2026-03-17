<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Community\Feedback\Filament\Admin\Resources\Feedback\FeedbackResource;

it('can register resources', function (): void {
    filament()->setCurrentPanel('admin');
    expect(Filament::getResources())
        ->toContain(FeedbackResource::class);
});
