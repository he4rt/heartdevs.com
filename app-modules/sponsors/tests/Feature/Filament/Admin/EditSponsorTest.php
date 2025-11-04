<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Sponsors\Filament\Resources\Sponsors\Pages\EditSponsor;
use He4rt\Sponsors\Models\Sponsor;

use function Pest\Livewire\livewire;

it('should render', function (): void {
    $sponsor = Sponsor::factory()->create();
    Filament::setCurrentPanel(FilamentPanel::Admin->value);
    $this->actingAsAdmin();

    livewire(EditSponsor::class, ['record' => $sponsor->getKey()])
        ->assertOk();
});
