<?php

declare(strict_types=1);

use He4rt\Events\CheckIn\Enums\CheckInMethod;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\CheckIn\Models\CheckInCode;
use He4rt\Events\CheckIn\Models\QrToken;
use He4rt\Events\Enrollment\Enums\AttendanceRequirement;
use He4rt\Events\Enrollment\Enums\EnrollmentMethod;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Enrollment\Models\EnrollmentTransition;
use He4rt\Events\Event\Enums\EventStatus;
use He4rt\Events\Event\Enums\EventType;
use He4rt\Events\Event\Models\Event;
use Illuminate\Database\Eloquent\Collection;

test('when creating an event via factory, then it is persisted with valid timestamps', function (): void {
    $event = Event::factory()->create();

    expect($event->id)->not->toBeNull()
        ->and($event->title)->not->toBeEmpty()
        ->and($event->starts_at)->not->toBeNull()
        ->and($event->ends_at->isAfter($event->starts_at))->toBeTrue();
});

test('when creating an enrollment policy via factory, then it is linked to an event', function (): void {
    $policy = EnrollmentPolicy::factory()->create();

    expect($policy->id)->not->toBeNull()
        ->and($policy->event_id)->not->toBeNull()
        ->and($policy->event)->toBeInstanceOf(Event::class);
});

test('when creating an enrollment via factory, then it is linked to an event and user', function (): void {
    $enrollment = Enrollment::factory()->create();

    expect($enrollment->id)->not->toBeNull()
        ->and($enrollment->event_id)->not->toBeNull()
        ->and($enrollment->user_id)->not->toBeNull();
});

test('when creating an enrollment transition via factory, then it is linked to an enrollment', function (): void {
    $transition = EnrollmentTransition::factory()->create();

    expect($transition->id)->not->toBeNull()
        ->and($transition->enrollment_id)->not->toBeNull()
        ->and($transition->to_status)->not->toBeNull();
});

test('when creating a check-in via factory, then it is linked to an enrollment', function (): void {
    $checkIn = CheckIn::factory()->create();

    expect($checkIn->id)->not->toBeNull()
        ->and($checkIn->enrollment_id)->not->toBeNull()
        ->and($checkIn->event_date)->not->toBeNull();
});

test('when creating a check-in code via factory, then it is linked to an event with valid dates', function (): void {
    $code = CheckInCode::factory()->create();

    expect($code->id)->not->toBeNull()
        ->and($code->event_id)->not->toBeNull()
        ->and($code->code)->not->toBeEmpty()
        ->and($code->expires_at->isAfter($code->starts_at))->toBeTrue();
});

test('when creating a qr token via factory, then it is linked to an enrollment with a 64-char token', function (): void {
    $token = QrToken::factory()->create();

    expect($token->id)->not->toBeNull()
        ->and($token->enrollment_id)->not->toBeNull()
        ->and($token->token)->toHaveLength(64);
});

test('when an event has an enrollment policy, then it is accessible via the relationship', function (): void {
    $event = Event::factory()
        ->has(EnrollmentPolicy::factory(), 'enrollmentPolicy')
        ->create();

    expect($event->enrollmentPolicy)->toBeInstanceOf(EnrollmentPolicy::class)
        ->and($event->enrollmentPolicy->event_id)->toBe($event->id);
});

test('when an event has enrollments, then they are accessible via the relationship', function (): void {
    $event = Event::factory()
        ->has(Enrollment::factory()->count(3), 'enrollments')
        ->create();

    expect($event->enrollments)->toBeInstanceOf(Collection::class)
        ->and($event->enrollments)->toHaveCount(3)
        ->and($event->enrollments->first()->event_id)->toBe($event->id);
});

test('when an event has check-in codes, then they are accessible via the relationship', function (): void {
    $event = Event::factory()
        ->has(CheckInCode::factory()->count(2), 'checkInCodes')
        ->create();

    expect($event->checkInCodes)->toHaveCount(2)
        ->and($event->checkInCodes->first()->event_id)->toBe($event->id);
});

test('when creating a workshop via factory preset, then event and enrollment policy match community defaults', function (): void {
    $event = Event::factory()->asWorkshop()->upcoming()->create();

    $event->load('enrollmentPolicy');

    expect($event->event_type)->toBe(EventType::Workshop)
        ->and($event->status)->toBe(EventStatus::Published)
        ->and($event->enrollmentPolicy)->not->toBeNull()
        ->and($event->enrollmentPolicy->enrollment_method)->toBe(EnrollmentMethod::RsvpCheckin)
        ->and($event->enrollmentPolicy->check_in_method)->toBe(CheckInMethod::NumericCode)
        ->and($event->enrollmentPolicy->capacity)->toBe(50)
        ->and($event->enrollmentPolicy->has_waitlist)->toBeTrue()
        ->and($event->enrollmentPolicy->attendance_requirement)->toBe(AttendanceRequirement::AllDays)
        ->and($event->enrollmentPolicy->xp_on_confirmed)->toBe(100);
});

test('when creating a meetup via factory preset, then event has open rsvp enrollment policy', function (): void {
    $event = Event::factory()->asMeetup()->create();

    $event->load('enrollmentPolicy');

    expect($event->event_type)->toBe(EventType::Meetup)
        ->and($event->enrollmentPolicy->enrollment_method)->toBe(EnrollmentMethod::Rsvp)
        ->and($event->enrollmentPolicy->check_in_method)->toBe(CheckInMethod::Manual)
        ->and($event->enrollmentPolicy->capacity)->toBeNull()
        ->and($event->enrollmentPolicy->has_waitlist)->toBeFalse();
});

test('when creating a conference via factory preset, then event has application enrollment with waitlist', function (): void {
    $event = Event::factory()->asConference()->past()->create();

    $event->load('enrollmentPolicy');

    expect($event->event_type)->toBe(EventType::Conference)
        ->and($event->enrollmentPolicy->enrollment_method)->toBe(EnrollmentMethod::Application)
        ->and($event->enrollmentPolicy->check_in_method)->toBe(CheckInMethod::QrCode)
        ->and($event->enrollmentPolicy->capacity)->toBe(200)
        ->and($event->enrollmentPolicy->has_waitlist)->toBeTrue()
        ->and($event->enrollmentPolicy->attendance_requirement)->toBe(AttendanceRequirement::MinimumDays)
        ->and($event->enrollmentPolicy->minimum_days)->toBe(2)
        ->and($event->enrollmentPolicy->cancellation_deadline_hours)->toBe(48);
});

test('when creating multiple meetup presets, then each event gets its own enrollment policy', function (): void {
    $events = Event::factory()->count(3)->asMeetup()->create();

    expect($events)->toHaveCount(3);

    foreach ($events as $event) {
        $event->load('enrollmentPolicy');

        expect($event->enrollmentPolicy)->not->toBeNull()
            ->and($event->enrollmentPolicy->enrollment_method)->toBe(EnrollmentMethod::Rsvp);
    }
});
