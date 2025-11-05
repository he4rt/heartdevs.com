<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\User\Filament\Admin\Resources\Users\UserResource;

it('can register resources', function (): void {
    expect(Filament::getResources())
        ->toContain(UserResource::class);
});





