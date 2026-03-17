<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Activity\Filament\Admin\Resources\Messages\MessageResource;

it('can render the list page', function (): void {
    expect(Filament::getResources())
        ->toContain(MessageResource::class);
});
