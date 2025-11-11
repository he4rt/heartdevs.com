<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Season\Filament\Resources\Seasons\Pages\ListSeasons;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(FilamentPanel::Admin->value);
    $this->actingAsAdmin();
});

it('should render', function (): void {
    livewire(ListSeasons::class)
        ->assertOk();
});
