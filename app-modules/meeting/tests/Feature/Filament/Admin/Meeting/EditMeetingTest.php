<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Meeting\Filament\Resources\Meetings\Pages\EditMeeting;
use He4rt\Meeting\Models\Meeting;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(FilamentPanel::Admin->value);
    $this->actingAsAdmin();
});

it('should render', function (): void {
    $meeting = Meeting::factory()->create();
    livewire(EditMeeting::class, ['record' => $meeting->getKey()])
        ->assertOk();
});
