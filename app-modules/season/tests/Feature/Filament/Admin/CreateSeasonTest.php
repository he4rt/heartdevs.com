<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Season\Filament\Resources\Seasons\Pages\CreateSeason;
use He4rt\Season\Models\Season;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(FilamentPanel::Admin->value);
    $this->actingAsAdmin();
});

it('should render', function (): void {
    livewire(CreateSeason::class)
        ->assertOk();
});

it('should be able to create a new season', function (): void {
    livewire(CreateSeason::class)
        ->assertOk()
        ->fillForm([
            'name' => 'season 1',
            'description' => 'description da season',
            'started_at' => Date::yesterday(),
            'ended_at' => Date::tomorrow(),
            'messages_count' => 5,
            'participants_count' => 5,
            'meeting_count' => 5,
            'badges_count' => 5,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseCount(Season::class, 1);
    assertDatabaseHas(Season::class, [
        'name' => 'season 1',
        'started_at' => Date::yesterday(),
        'ended_at' => Date::tomorrow(),
        'messages_count' => 5,
        'participants_count' => 5,
        'meeting_count' => 5,
        'badges_count' => 5,
    ]);
});
