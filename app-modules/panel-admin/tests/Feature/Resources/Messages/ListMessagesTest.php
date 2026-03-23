<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\PanelAdmin\Filament\Resources\Messages\MessageResource;
use He4rt\PanelAdmin\Filament\Resources\Messages\Pages\ListMessages;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel(FilamentPanel::Admin->value));
    $this->actingAsAdmin();
});

it('is registered in admin panel', function (): void {
    expect(Filament::getResources())
        ->toContain(MessageResource::class);
});

it('can render', function (): void {
    livewire(ListMessages::class)->assertOk();
});
