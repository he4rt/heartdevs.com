<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Meeting\Filament\Resources\MeetingTypes\Pages\EditMeetingType;
use He4rt\Meeting\Models\MeetingType;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(FilamentPanel::Admin->value);
    $this->actingAsAdmin();
});

it('should render', function (): void {
    $meetingType = MeetingType::factory()->create();
    livewire(EditMeetingType::class, ['record' => $meetingType->getKey()])
        ->assertOk();
});
