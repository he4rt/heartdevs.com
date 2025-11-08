<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Events\Filament\Resources\Events\Pages\EditEvent;
use He4rt\Events\Models\EventModel;

use function Pest\Livewire\livewire;

it('should render', function (): void {
    Filament::setCurrentPanel(FilamentPanel::Admin->value);
    $event = EventModel::factory()->createOne();
    $this->actingAsAdmin();
    livewire(EditEvent::class, ['record' => $event->getKey()])
        ->assertOk();
});
