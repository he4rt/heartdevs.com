<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Livewire\Events\EventDetail;
use He4rt\PanelApp\Livewire\Events\MyEventsList;
use He4rt\PanelApp\Pages\EventPage;
use He4rt\PanelApp\Pages\EventsPage;
use He4rt\PanelApp\Pages\MyEventsPage;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->tenant = Tenant::factory()->create(['slug' => 'test-tenant']);
    $this->tenant->members()->attach($this->user);

    $this->actingAs($this->user);

    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->tenant);

    $this->event = Event::factory()
        ->published()
        ->upcoming()
        ->for($this->tenant)
        ->has(EnrollmentPolicy::factory()->rsvp(), 'enrollmentPolicy')
        ->create(['title' => 'He4rt Meetup RSVP']);
});

test('events page renders successfully', function (): void {
    $this->get(EventsPage::getUrl())
        ->assertSuccessful()
        ->assertSee('He4rt Meetup RSVP');
});

test('event page renders confirm presence button for rsvp event', function (): void {
    $this->get(EventPage::getUrl(['record' => $this->event->id]))
        ->assertSuccessful()
        ->assertSee('He4rt Meetup RSVP')
        ->assertSee(__('events::pages.confirm_presence'));
});

test('when user confirms presence, then enrollment is created and shown in my events', function (): void {
    livewire(EventDetail::class, ['eventId' => $this->event->id])
        ->call('confirmPresence')
        ->assertHasNoErrors();

    $enrollment = Enrollment::query()
        ->where('event_id', $this->event->id)
        ->where('user_id', $this->user->id)
        ->first();

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->status)->toBe(EnrollmentStatus::Confirmed);

    $this->get(MyEventsPage::getUrl())
        ->assertSuccessful()
        ->assertSee('He4rt Meetup RSVP')
        ->assertSee(EnrollmentStatus::Confirmed->getLabel());
});

test('when user tries to confirm presence twice, then duplicate enrollment is rejected', function (): void {
    livewire(EventDetail::class, ['eventId' => $this->event->id])
        ->call('confirmPresence');

    livewire(EventDetail::class, ['eventId' => $this->event->id])
        ->call('confirmPresence')
        ->assertNotified();

    expect(Enrollment::query()->where('event_id', $this->event->id)->count())->toBe(1);
});

test('event page returns 404 for event from another tenant', function (): void {
    $otherTenant = Tenant::factory()->create(['slug' => 'other-tenant']);
    $otherEvent = Event::factory()
        ->published()
        ->upcoming()
        ->for($otherTenant)
        ->has(EnrollmentPolicy::factory()->rsvp(), 'enrollmentPolicy')
        ->create();

    $this->get(EventPage::getUrl(['record' => $otherEvent->id]))
        ->assertNotFound();
});

test('when event is at capacity without waitlist, then event full message is shown', function (): void {
    $this->event->enrollmentPolicy->update([
        'capacity' => 1,
        'has_waitlist' => false,
    ]);

    $otherUser = User::factory()->create();
    Enrollment::factory()->create([
        'event_id' => $this->event->id,
        'user_id' => $otherUser->id,
        'status' => EnrollmentStatus::Confirmed,
        'enrolled_at' => now(),
        'confirmed_at' => now(),
    ]);

    livewire(EventDetail::class, ['eventId' => $this->event->id])
        ->assertSet('canConfirmPresence', false)
        ->assertSet('isEventFull', true)
        ->assertSee(__('events::pages.event_full'))
        ->assertDontSee(__('events::pages.confirm_presence'));
});

test('when user is waitlisted, then waitlist position is shown on event page', function (): void {
    $this->event->enrollmentPolicy->update([
        'capacity' => 1,
        'has_waitlist' => true,
    ]);

    $otherUser = User::factory()->create();
    Enrollment::factory()->create([
        'event_id' => $this->event->id,
        'user_id' => $otherUser->id,
        'status' => EnrollmentStatus::Confirmed,
        'enrolled_at' => now(),
        'confirmed_at' => now(),
    ]);

    livewire(EventDetail::class, ['eventId' => $this->event->id])
        ->call('confirmPresence')
        ->assertHasNoErrors()
        ->assertSee(__('events::pages.waitlist_status', ['position' => 1]));
});

test('when event is at capacity with waitlist, then confirm presence button is still shown', function (): void {
    $this->event->enrollmentPolicy->update([
        'capacity' => 1,
        'has_waitlist' => true,
    ]);

    $otherUser = User::factory()->create();
    Enrollment::factory()->create([
        'event_id' => $this->event->id,
        'user_id' => $otherUser->id,
        'status' => EnrollmentStatus::Confirmed,
        'enrolled_at' => now(),
        'confirmed_at' => now(),
    ]);

    livewire(EventDetail::class, ['eventId' => $this->event->id])
        ->assertSet('canConfirmPresence', true)
        ->assertSee(__('events::pages.confirm_presence'));
});

test('when enrolled event is completed, then event detail page is accessible', function (): void {
    $this->event->update(['status' => EventStatus::Completed]);

    Enrollment::factory()->create([
        'event_id' => $this->event->id,
        'user_id' => $this->user->id,
        'status' => EnrollmentStatus::Confirmed,
        'enrolled_at' => now(),
        'confirmed_at' => now(),
    ]);

    $this->get(EventPage::getUrl(['record' => $this->event->id]))
        ->assertSuccessful()
        ->assertSee('He4rt Meetup RSVP');

    livewire(EventDetail::class, ['eventId' => $this->event->id])
        ->assertSet('canConfirmPresence', false);
});

test('when enrolled event is draft, then my events list does not link to detail', function (): void {
    $this->event->update(['status' => EventStatus::Draft]);

    Enrollment::factory()->create([
        'event_id' => $this->event->id,
        'user_id' => $this->user->id,
        'status' => EnrollmentStatus::Confirmed,
        'enrolled_at' => now(),
        'confirmed_at' => now(),
    ]);

    livewire(MyEventsList::class)
        ->assertSet('enrollments', fn ($enrollments): bool => $enrollments->count() === 1)
        ->assertSee('He4rt Meetup RSVP')
        ->assertDontSee(EventPage::getUrl(['record' => $this->event->id]), false);
});

test('past event does not show confirm presence button', function (): void {
    $pastEvent = Event::factory()
        ->published()
        ->past()
        ->for($this->tenant)
        ->has(EnrollmentPolicy::factory()->rsvp(), 'enrollmentPolicy')
        ->create(['title' => 'Past Meetup']);

    livewire(EventDetail::class, ['eventId' => $pastEvent->id])
        ->assertSet('canConfirmPresence', false)
        ->assertDontSee(__('events::pages.confirm_presence'));
});
