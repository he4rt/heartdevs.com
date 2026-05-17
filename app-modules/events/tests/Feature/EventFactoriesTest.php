<?php

declare(strict_types=1);

use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\CheckIn\Models\CheckInCode;
use He4rt\Events\CheckIn\Models\QrToken;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Enrollment\Models\EnrollmentPolicy;
use He4rt\Events\Enrollment\Models\EnrollmentTransition;
use He4rt\Events\Event\Models\Event;

test('event factory creates a persisted event', function (): void {
    $event = Event::factory()->create();

    expect($event->id)->not->toBeNull()
        ->and($event->title)->not->toBeEmpty()
        ->and($event->starts_at)->not->toBeNull()
        ->and($event->ends_at->isAfter($event->starts_at))->toBeTrue();
});

test('enrollment policy factory creates a policy linked to an event', function (): void {
    $policy = EnrollmentPolicy::factory()->create();

    expect($policy->id)->not->toBeNull()
        ->and($policy->event_id)->not->toBeNull()
        ->and($policy->event)->toBeInstanceOf(Event::class);
});

test('enrollment factory creates an enrollment linked to event and user', function (): void {
    $enrollment = Enrollment::factory()->create();

    expect($enrollment->id)->not->toBeNull()
        ->and($enrollment->event_id)->not->toBeNull()
        ->and($enrollment->user_id)->not->toBeNull();
});

test('enrollment transition factory creates a transition linked to enrollment', function (): void {
    $transition = EnrollmentTransition::factory()->create();

    expect($transition->id)->not->toBeNull()
        ->and($transition->enrollment_id)->not->toBeNull()
        ->and($transition->to_status)->not->toBeNull();
});

test('check-in factory creates a check-in linked to enrollment', function (): void {
    $checkIn = CheckIn::factory()->create();

    expect($checkIn->id)->not->toBeNull()
        ->and($checkIn->enrollment_id)->not->toBeNull()
        ->and($checkIn->check_in_date)->not->toBeNull();
});

test('check-in code factory creates a code linked to event', function (): void {
    $code = CheckInCode::factory()->create();

    expect($code->id)->not->toBeNull()
        ->and($code->event_id)->not->toBeNull()
        ->and($code->code)->not->toBeEmpty()
        ->and($code->expires_at->isAfter($code->starts_at))->toBeTrue();
});

test('qr token factory creates a token linked to enrollment', function (): void {
    $token = QrToken::factory()->create();

    expect($token->id)->not->toBeNull()
        ->and($token->enrollment_id)->not->toBeNull()
        ->and(mb_strlen($token->token))->toBe(64);
});
