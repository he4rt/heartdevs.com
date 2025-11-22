<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Season\Filament\Admin\Resources\Seasons\Pages\EditSeason;
use He4rt\Season\Models\Season;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(FilamentPanel::Admin->value);
    $this->actingAsAdmin();
});

it('should render', function (): void {
    $season = Season::factory()->create();
    livewire(EditSeason::class, ['record' => $season->getKey()])
        ->assertOk();
});
