<?php
declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Feedback\Filament\Admin\Resources\Feedback\FeedbackResource;

it('can register resources', function (): void {
    expect(Filament::getResources())
        ->toContain(FeedbackResource::class);
});



