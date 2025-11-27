<?php

declare(strict_types=1);

use App\Enums\FilamentPanel;
use Filament\Facades\Filament;
use He4rt\Events\Enums\Talks\TalkStatusEnum;
use He4rt\Events\Filament\App\Talks\Pages\CreateTalk;
use He4rt\Events\Models\EventModel;
use He4rt\Events\Models\EventSubmission;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(FilamentPanel::User->value);
    actingAs(User::factory()->create());
    $this->tenant = Tenant::factory()->create();
    Filament::setTenant($this->tenant);
    $this->event = EventModel::factory()->recycle($this->tenant)->create();
});

it('should render', function (): void {
    livewire(CreateTalk::class)
        ->assertOk();
});

it('should send a talk call for paper', function (): void {
    livewire(CreateTalk::class)
        ->assertOk()
        ->fillForm([
            'event_id' => $this->event->getKey(),
            'field_type' => 'whatever',
            'title' => 'title whatever',
            'description' => 'description whatever',
            'starts_at' => now()->addMinutes(10),
            'ends_at' => now()->addHour(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(EventSubmission::class, [
        'event_id' => $this->event->getKey(),
        'field_type' => 'whatever',
        'title' => 'title whatever',
        'user_id' => auth()->user()->getKey(),
        'tenant_id' => Filament::getTenant()->getKey(),
    ]);
});
it('should create talk that only  events that belongs to the tenant', function (): void {
    livewire(CreateTalk::class)
        ->assertOk()
        ->fillForm([
            'event_id' => EventModel::factory()->create()->getKey(),
            'field_type' => 'whatever',
            'title' => 'title whatever',
            'description' => 'description whatever',
        ])
        ->call('create')
        ->assertHasFormErrors(['event_id']);

    assertDatabaseMissing(EventSubmission::class, [
        'event_id' => EventModel::factory()->create()->getKey(),
        'field_type' => 'whatever',
        'title' => 'title whatever',
    ]);
});

describe('TalkTimeIsAvailable Rule', function (): void {

    it('should should not be able to create a talk if there is already one at this time', function (): void {
        $start = now()->addHour();
        $end = now()->addhours(3);
        EventSubmission::factory()->recycle($this->event)->create([
            'starts_at' => $start,
            'ends_at' => $end,
            'status' => TalkStatusEnum::Accepted,
        ]);
        livewire(CreateTalk::class)
            ->assertOk()
            ->fillForm([
                'event_id' => $this->event->getKey(),
                'field_type' => 'whatever',
                'title' => 'title whatever',
                'description' => 'description whatever',
                'starts_at' => $start,
                'ends_at' => $end,
            ])
            ->call('create')
            ->assertHasFormErrors(['ends_at']);
    });
    it('should should be able to create a talk if there is already one but is not accepted yet', function (): void {

        $start = now()->addHour();
        $end = now()->addhours(3);

        EventSubmission::factory()->recycle($this->event)->create([
            'starts_at' => $start,
            'ends_at' => $end,
            'status' => TalkStatusEnum::Pending,
        ]);
        livewire(CreateTalk::class)
            ->assertOk()
            ->fillForm([
                'event_id' => $this->event->getKey(),
                'field_type' => 'whatever',
                'title' => 'title whatever',
                'description' => 'description whatever',
                'starts_at' => $start,
                'ends_at' => $end,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    })->skip();
});

describe('validation rules', function (): void {
    test('field_type::validations', function ($rule, $value): void {
        livewire(CreateTalk::class)
            ->assertOk()
            ->fillForm([
                'field_type' => $value,

            ])
            ->call('create')
            ->assertHasNoFormErrors(['field_type' => $rule]);
    })->with([
        'required' => ['', 'required'],
        'min:3' => ['aa', 'min:3'],
        'max:256' => [str_repeat('a', 256), 'max:255'],
    ]);
    test('title::validations', function ($rule, $value): void {
        livewire(CreateTalk::class)
            ->assertOk()
            ->fillForm([
                'title' => $value,

            ])
            ->call('create')
            ->assertHasNoFormErrors(['title' => $rule]);
    })->with([
        'required' => ['', 'required'],
        'min:3' => ['aa', 'min:3'],
        'max:256' => [str_repeat('a', 256), 'max:255'],
    ]);
});
