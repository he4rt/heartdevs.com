<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\Enrollment\Enums\AttendanceRequirement;
use He4rt\Events\Enrollment\Enums\EnrollmentMethod;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Event\Enums\EventType;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\PanelAdmin\Filament\Resources\Events\EventResource;
use He4rt\PanelAdmin\Filament\Resources\Events\Pages\CreateEvent;
use He4rt\PanelAdmin\Filament\Resources\Events\Pages\EditEvent;
use He4rt\PanelAdmin\Filament\Resources\Events\Pages\ListEvents;
use He4rt\PanelAdmin\Filament\Resources\Events\RelationManagers\EnrollmentsRelationManager;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $admin = User::factory()->create(['username' => 'events-test-admin']);
    $tenant = Tenant::factory()->create(['slug' => 'he4rt-dev']);
    $tenant->members()->attach($admin);

    config(['he4rt.admins' => 'events-test-admin']);
    $this->actingAs($admin);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($tenant);
});

test('when visiting the events list page, then it renders successfully', function (): void {
    livewire(ListEvents::class)
        ->assertSuccessful();
});

test('when an event exists, then it appears in the events list', function (): void {
    $event = Event::factory()->create(['title' => 'He4rt Meetup #42']);

    livewire(ListEvents::class)
        ->assertSee($event->title);
});

test('when visiting the create event page, then it renders successfully', function (): void {
    livewire(CreateEvent::class)
        ->assertSuccessful();
});

test('when visiting the edit event page, then it renders successfully', function (): void {
    $event = Event::factory()->create();

    livewire(EditEvent::class, ['record' => $event->getRouteKey()])
        ->assertSuccessful();
});

test('when checking the event resource model, then it points to Event', function (): void {
    expect(EventResource::getModel())->toBe(Event::class);
});

test('when submitting the create form with valid data, then event and enrollment policy are persisted', function (): void {
    $startsAt = now()->addDay();

    livewire(CreateEvent::class)
        ->fillForm([
            'title' => 'He4rt Meetup #42',
            'slug' => 'he4rt-meetup-42',
            'event_type' => EventType::Meetup,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->clone()->addHours(3),
            'enrollmentPolicy' => [
                'enrollment_method' => EnrollmentMethod::Rsvp,
                'check_in_method' => CheckInMethod::Manual,
                'attendance_requirement' => AttendanceRequirement::AllDays,
                'xp_on_confirmed' => 0,
                'xp_on_checked_in' => 0,
                'xp_on_attended' => 0,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $event = Event::query()->where('slug', 'he4rt-meetup-42')->first();
    expect($event)->not->toBeNull()
        ->and($event->title)->toBe('He4rt Meetup #42')
        ->and($event->enrollmentPolicy)->not->toBeNull()
        ->and($event->enrollmentPolicy->enrollment_method)->toBe(EnrollmentMethod::Rsvp);
});

test('when submitting the edit form with a new title, then it is updated in the database', function (): void {
    $event = Event::factory()
        ->has(EnrollmentPolicy::factory(), 'enrollmentPolicy')
        ->create(['title' => 'Old Title']);

    livewire(EditEvent::class, ['record' => $event->getRouteKey()])
        ->fillForm(['title' => 'New Title'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($event->fresh()->title)->toBe('New Title');
});

test('when visiting the enrollments relation manager, then it renders successfully', function (): void {
    $event = Event::factory()->create();

    livewire(EnrollmentsRelationManager::class, [
        'ownerRecord' => $event,
        'pageClass' => EditEvent::class,
    ])
        ->assertSuccessful();
});
