<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\PanelAdmin\Filament\Resources\EventModels\Pages\ListEventModels;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel(FilamentPanel::Admin->value));
    $this->actingAsAdmin();
});

it('can render', function (): void {
    livewire(ListEventModels::class)->assertOk();
});
