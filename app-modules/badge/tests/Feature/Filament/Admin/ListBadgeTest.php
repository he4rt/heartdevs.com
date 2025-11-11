<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Badge\Filament\Resources\Badges\Pages\ListBadges;
use He4rt\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('should render', function (): void {
    Filament::setCurrentPanel('admin');
    actingAs(User::factory()->createOne());
    livewire(ListBadges::class)
        ->assertOk();
});
