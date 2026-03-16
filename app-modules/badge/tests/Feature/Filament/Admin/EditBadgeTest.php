<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Badge\Filament\Resources\Badges\Pages\EditBadge;
use He4rt\Badge\Models\Badge;
use He4rt\Identity\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('should render', function (): void {
    Filament::setCurrentPanel('admin');
    actingAs(User::factory()->createOne());
    $badge = Badge::factory()->create();

    livewire(EditBadge::class, ['record' => $badge->getKey()])
        ->assertOk();
});
