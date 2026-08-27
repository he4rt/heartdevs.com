<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Livewire\Events\EventDetail;
use He4rt\PanelApp\Pages\EventPage;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->actingAs($this->user);

    Filament::setCurrentPanel(Filament::getPanel('app'));

    $this->schema = [
        ['key' => 'why_join', 'type' => 'text', 'label' => 'Why do you want to join?', 'required' => true],
        ['key' => 'experience_level', 'type' => 'select', 'label' => 'Experience level', 'required' => false, 'options' => ['Beginner', 'Intermediate', 'Advanced']],
    ];

    $this->event = Event::factory()
        ->published()
        ->upcoming()
        ->has(EnrollmentPolicy::factory()->application($this->schema), 'enrollmentPolicy')
        ->create(['title' => 'He4rt Conf Application']);
});

test('event page renders apply button for application event', function (): void {
    $this->get(EventPage::getUrl(['record' => $this->event->id]))
        ->assertSuccessful()
        ->assertSee('He4rt Conf Application')
        ->assertSee(__('events::pages.apply_submit'));
});

test('event detail shows canApply true and canConfirmPresence false for application event', function (): void {
    livewire(EventDetail::class, ['eventId' => $this->event->id])
        ->assertSet('canApply', value: true)
        ->assertSet('canConfirmPresence', value: false);
});

test('when user submits application, then enrollment is created as pending', function (): void {
    livewire(EventDetail::class, ['eventId' => $this->event->id])
        ->set('applicationFormData', ['why_join' => 'I love Laravel!', 'experience_level' => 'Intermediate'])
        ->call('apply')
        ->assertHasNoErrors()
        ->assertNotified();

    $enrollment = Enrollment::query()
        ->where('event_id', $this->event->id)
        ->where('user_id', $this->user->id)
        ->first();

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->status)->toBe(EnrollmentStatus::Pending)
        ->and($enrollment->confirmed_at)->toBeNull()
        ->and($enrollment->application_data['why_join'])->toBe('I love Laravel!');
});

test('after submitting application, event detail shows pending status with answers', function (): void {
    livewire(EventDetail::class, ['eventId' => $this->event->id])
        ->set('applicationFormData', ['why_join' => 'I love Laravel!', 'experience_level' => 'Intermediate'])
        ->call('apply');

    livewire(EventDetail::class, ['eventId' => $this->event->id])
        ->assertSet('canApply', value: false)
        ->assertSee(EnrollmentStatus::Pending->getLabel())
        ->assertSee(__('events::pages.application_pending_hint'))
        ->assertSee('I love Laravel!');
});

test('when application is rejected, then rejection reason is shown', function (): void {
    Enrollment::factory()->create([
        'event_id' => $this->event->id,
        'user_id' => $this->user->id,
        'status' => EnrollmentStatus::Rejected,
        'enrolled_at' => now(),
        'rejection_reason' => 'Not enough experience.',
        'application_data' => ['why_join' => 'I am new'],
    ]);

    livewire(EventDetail::class, ['eventId' => $this->event->id])
        ->assertSet('canApply', value: false)
        ->assertSee(EnrollmentStatus::Rejected->getLabel())
        ->assertSee(__('events::pages.application_rejected_hint'))
        ->assertSee('Not enough experience.');
});

test('when user has already applied, then apply button is not shown', function (): void {
    Enrollment::factory()->create([
        'event_id' => $this->event->id,
        'user_id' => $this->user->id,
        'status' => EnrollmentStatus::Pending,
        'enrolled_at' => now(),
        'application_data' => ['why_join' => 'My answer'],
    ]);

    livewire(EventDetail::class, ['eventId' => $this->event->id])
        ->assertSet('canApply', value: false)
        ->assertDontSee(__('events::pages.apply_submit'));
});

test('when questions are reordered after submission, then answers still match the correct question', function (): void {
    livewire(EventDetail::class, ['eventId' => $this->event->id])
        ->set('applicationFormData', ['why_join' => 'I love Laravel!', 'experience_level' => 'Intermediate'])
        ->call('apply');

    $this->event->enrollmentPolicy->update([
        'application_schema' => array_reverse($this->schema),
    ]);

    livewire(EventDetail::class, ['eventId' => $this->event->id])
        ->assertSeeInOrder([
            'Experience level',
            'Intermediate',
            'Why do you want to join?',
            'I love Laravel!',
        ]);
});

test('when application data is missing required field, then error notification is shown', function (): void {
    livewire(EventDetail::class, ['eventId' => $this->event->id])
        ->set('applicationFormData', ['why_join' => '', 'experience_level' => 'Beginner'])
        ->call('apply')
        ->assertNotified();

    expect(Enrollment::query()->where('event_id', $this->event->id)->count())->toBe(0);
});
