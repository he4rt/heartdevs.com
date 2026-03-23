<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Gamification\Season\Models\Season;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\PanelAdmin\Filament\Resources\Seasons\Pages\CreateSeason;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel(FilamentPanel::Admin->value));
    $this->actingAsAdmin();
    $this->tenant = Tenant::query()->first();
});

it('can render', function (): void {
    livewire(CreateSeason::class)->assertOk();
});

it('can create a season', function (): void {
    livewire(CreateSeason::class)
        ->fillForm([
            'tenant_id' => $this->tenant->getKey(),
            'name' => 'Season 1',
            'description' => 'First season',
            'started_at' => now(),
            'ended_at' => now()->addMonths(3),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Season::class, [
        'name' => 'Season 1',
    ]);
});

it('validates form data', function (string $field, mixed $value, string $rule): void {
    livewire(CreateSeason::class)
        ->fillForm([$field => $value])
        ->call('create')
        ->assertHasFormErrors([$field => $rule]);
})->with([
    'name is required' => ['name', '', 'required'],
    'started_at is required' => ['started_at', null, 'required'],
]);
