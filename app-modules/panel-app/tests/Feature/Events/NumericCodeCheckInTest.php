<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Event\Models\Event;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Livewire\Events\NumericCodeCheckIn;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->actingAs($this->user);

    Filament::setCurrentPanel(Filament::getPanel('app'));

    $this->event = Event::factory()
        ->published()
        ->upcoming()
        ->has(EnrollmentPolicy::factory()->rsvp()->state([
            'check_in_method' => CheckInMethod::NumericCode,
        ]), 'enrollmentPolicy')
        ->create();

    Enrollment::factory()->create([
        'event_id' => $this->event->id,
        'user_id' => $this->user->id,
        'status' => EnrollmentStatus::Confirmed,
        'confirmed_at' => now(),
    ]);

    RateLimiter::clear(sprintf('numeric-code-check-in:%s:%s:%s', $this->user->id, $this->event->id, md5('127.0.0.1')));
});

test('when check-in code format is invalid, then component rejects it before action', function (): void {
    livewire(NumericCodeCheckIn::class, ['eventId' => $this->event->id])
        ->set('code', 'abc123')
        ->call('checkIn')
        ->assertHasErrors(['code']);
});

test('when participant repeats invalid numeric codes, then component rate limits attempts', function (): void {
    $component = livewire(NumericCodeCheckIn::class, ['eventId' => $this->event->id])
        ->set('code', '999999');

    foreach (range(1, 5) as $_) {
        $component
            ->call('checkIn')
            ->assertSet('error', __('events::check_in.invalid_check_in_code'));
    }

    $component
        ->call('checkIn')
        ->assertSet('error', fn (string $error): bool => str_contains($error, 'Too many attempts.'));
});
