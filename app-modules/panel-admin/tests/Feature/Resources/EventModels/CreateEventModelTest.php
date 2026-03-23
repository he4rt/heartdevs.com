<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Events\Enums\EventTypeEnum;
use He4rt\Events\Models\EventModel;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\PanelAdmin\Filament\Resources\EventModels\Pages\CreateEventModel;
use Illuminate\Support\Facades\Date;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel(FilamentPanel::Admin->value));
    $this->actingAsAdmin();
    $this->tenant = Tenant::query()->first();
});

it('can render', function (): void {
    livewire(CreateEventModel::class)->assertOk();
});

it('can create an event', function (): void {
    livewire(CreateEventModel::class)
        ->fillForm([
            'tenant_id' => $this->tenant->getKey(),
            'title' => 'event title',
            'slug' => 'event-slug',
            'description' => 'event description',
            'location' => 'event location',
            'max_attendees' => 5,
            'event_type' => EventTypeEnum::Workshop->value,
            'active' => true,
            'event_at' => today(),
            'start_at' => today(),
            'end_at' => Date::tomorrow(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(EventModel::class, [
        'title' => 'event title',
        'slug' => 'event-slug',
    ]);
});

it('validates form data', function (string $field, mixed $value, string $rule): void {
    livewire(CreateEventModel::class)
        ->fillForm([$field => $value])
        ->call('create')
        ->assertHasFormErrors([$field => $rule]);
})->with([
    'title is required' => ['title', '', 'required'],
    'title min 5' => ['title', 'ab', 'min'],
    'location is required' => ['location', '', 'required'],
    'location min 5' => ['location', 'ab', 'min'],
    'max_attendees is required' => ['max_attendees', null, 'required'],
    'event_at is required' => ['event_at', null, 'required'],
    'start_at is required' => ['start_at', null, 'required'],
    'end_at is required' => ['end_at', null, 'required'],
]);
