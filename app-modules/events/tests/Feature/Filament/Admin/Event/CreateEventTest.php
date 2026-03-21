<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Events\Enums\EventTypeEnum;
use He4rt\Events\Filament\Admin\Resources\Events\Pages\CreateEvent;
use He4rt\Events\Models\EventModel;
use He4rt\Identity\Tenant\Models\Tenant;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(FilamentPanel::Admin->value);
    $this->actingAsAdmin();
    $this->tenant = Tenant::query()->first();
});

it('should render', function (): void {
    livewire(CreateEvent::class)
        ->assertOk();
});

it('should be able to create an event', function (): void {
    livewire(CreateEvent::class)
        ->assertOk()
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

    assertDatabaseCount(EventModel::class, 1);
    assertDatabaseHas(EventModel::class, [
        'tenant_id' => $this->tenant->getKey(),
        'title' => 'event title',
        'slug' => 'event-slug',
        'location' => 'event location',
        'max_attendees' => 5,
        'event_type' => EventTypeEnum::Workshop->value,
        'active' => true,
        'event_at' => today(),
        'start_at' => today(),
        'end_at' => Date::tomorrow(),
    ]);
});

describe('validation tests', function (): void {

    test('title::validations', function ($value, $rule): void {
        livewire(CreateEvent::class)
            ->assertOk()
            ->fillForm([
                'title' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['title' => $rule]);
    })->with([
        'required' => ['', 'required'],
        'min' => ['aa', 'min:5'],
        'max' => [str_repeat('a', 256), 'max:255'],
    ]);
    test('location::validations', function ($value, $rule): void {
        livewire(CreateEvent::class)
            ->assertOk()
            ->fillForm([
                'location' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['location' => $rule]);
    })->with([
        'required' => ['', 'required'],
        'min' => ['aa', 'min:5'],
        'max' => [str_repeat('a', 256), 'max:255'],
    ]);

    test('event_type::validations', function ($value, $rule): void {
        livewire(CreateEvent::class)
            ->assertOk()
            ->fillForm([
                'event_type' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['event_type' => $rule]);
    })->with([
        'required' => ['', 'required'],
        'enum' => ['aa', 'The selected event Type is invalid.'],
    ]);

    test('max_attendees::validations', function ($value, $rule): void {
        livewire(CreateEvent::class)
            ->assertOk()
            ->fillForm([
                'max_attendees' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['max_attendees' => $rule]);
    })->with([
        'required' => [null, 'required'],
        'min 1' => [-1, 'min'],
    ]);

    test('active::validations', function ($value, $rule): void {
        livewire(CreateEvent::class)
            ->assertOk()
            ->fillForm([
                'active' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['active' => $rule]);
    })->with([
        'required' => [null, 'required'],
    ]);
    test('event_at::validations', function ($value, $rule): void {
        livewire(CreateEvent::class)
            ->assertOk()
            ->fillForm([
                'event_at' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['event_at' => $rule]);
    })->with([
        'required' => [null, 'required'],
    ]);

    test('start_at::validations', function ($value, $rule): void {
        livewire(CreateEvent::class)
            ->assertOk()
            ->fillForm([
                'start_at' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['start_at' => $rule]);
    })->with([
        'required' => [null, 'required'],
    ]);

    test('end_at::validations', function ($value, $rule): void {
        livewire(CreateEvent::class)
            ->assertOk()
            ->fillForm([
                'start_at' => now(),
                'end_at' => $value,
            ])
            ->call('create')
            ->assertHasFormErrors(['end_at' => $rule]);
    })->with([
        'required' => [null, 'required'],
        'after' => [Date::yesterday(), 'after'],
    ]);
});
