<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Community\Meeting\Filament\Resources\MeetingTypes\Pages\CreateMeetingType;
use He4rt\Community\Meeting\Models\MeetingType;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(FilamentPanel::Admin->value);
    $this->actingAsAdmin();
});

it('should render', function (): void {
    livewire(CreateMeetingType::class)
        ->assertOk();
});

it('should be able to create a meet', function (): void {
    livewire(CreateMeetingType::class)
        ->assertOk()
        ->fillForm([
            'name' => 'meeting type name',
            'week_day' => 1,
            'start_at' => '01:00:00',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseCount(MeetingType::class, 1);
    assertDatabaseHas(MeetingType::class, [
        'name' => 'meeting type name',
        'week_day' => 1,
        'start_at' => '01:00:00',
    ]);
});
