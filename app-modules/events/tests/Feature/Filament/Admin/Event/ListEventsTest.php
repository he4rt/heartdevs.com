<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Events\Filament\Resources\Events\Pages\ListEvents;

use function Pest\Livewire\livewire;

it('should render', function (): void {
    Filament::setCurrentPanel(FilamentPanel::Admin->value);
    $this->actingAsAdmin();
    livewire(ListEvents::class)
        ->assertOk();
});
