<?php

declare(strict_types=1);

use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;

test('when a pending enrollment is evaluated, then it can transition to confirmed, rejected, or cancelled', function (): void {
    expect(EnrollmentStatus::Pending->canTransitionTo(EnrollmentStatus::Confirmed))->toBeTrue()
        ->and(EnrollmentStatus::Pending->canTransitionTo(EnrollmentStatus::Rejected))->toBeTrue()
        ->and(EnrollmentStatus::Pending->canTransitionTo(EnrollmentStatus::Cancelled))->toBeTrue();
});

test('when a pending enrollment is evaluated, then it can transition to waitlisted when capacity is full', function (): void {
    expect(EnrollmentStatus::Pending->canTransitionTo(EnrollmentStatus::Waitlisted))->toBeTrue();
});

test('when a pending enrollment is evaluated, then it cannot transition to mid-flow or terminal states', function (): void {
    expect(EnrollmentStatus::Pending->canTransitionTo(EnrollmentStatus::CheckedIn))->toBeFalse()
        ->and(EnrollmentStatus::Pending->canTransitionTo(EnrollmentStatus::Attended))->toBeFalse()
        ->and(EnrollmentStatus::Pending->canTransitionTo(EnrollmentStatus::NoShow))->toBeFalse();
});

test('when a waitlisted enrollment is evaluated, then it can transition to confirmed or cancelled', function (): void {
    expect(EnrollmentStatus::Waitlisted->canTransitionTo(EnrollmentStatus::Confirmed))->toBeTrue()
        ->and(EnrollmentStatus::Waitlisted->canTransitionTo(EnrollmentStatus::Cancelled))->toBeTrue();
});

test('when a confirmed enrollment is evaluated, then it can transition to checked_in, cancelled, or no_show', function (): void {
    expect(EnrollmentStatus::Confirmed->canTransitionTo(EnrollmentStatus::CheckedIn))->toBeTrue()
        ->and(EnrollmentStatus::Confirmed->canTransitionTo(EnrollmentStatus::Cancelled))->toBeTrue()
        ->and(EnrollmentStatus::Confirmed->canTransitionTo(EnrollmentStatus::NoShow))->toBeTrue();
});

test('when a checked_in enrollment is evaluated, then it can transition to attended or no_show', function (): void {
    expect(EnrollmentStatus::CheckedIn->canTransitionTo(EnrollmentStatus::Attended))->toBeTrue()
        ->and(EnrollmentStatus::CheckedIn->canTransitionTo(EnrollmentStatus::NoShow))->toBeTrue()
        ->and(EnrollmentStatus::CheckedIn->canTransitionTo(EnrollmentStatus::Confirmed))->toBeFalse()
        ->and(EnrollmentStatus::CheckedIn->canTransitionTo(EnrollmentStatus::Cancelled))->toBeFalse();
});

test('when a terminal enrollment status is evaluated, then it cannot transition to any state', function (EnrollmentStatus $status): void {
    foreach (EnrollmentStatus::cases() as $target) {
        expect($status->canTransitionTo($target))->toBeFalse();
    }
})->with([
    EnrollmentStatus::Attended,
    EnrollmentStatus::Cancelled,
    EnrollmentStatus::Rejected,
    EnrollmentStatus::NoShow,
]);

test('when enrollment statuses are evaluated, then terminal states are correctly identified', function (): void {
    expect(EnrollmentStatus::Attended->isTerminal())->toBeTrue()
        ->and(EnrollmentStatus::Cancelled->isTerminal())->toBeTrue()
        ->and(EnrollmentStatus::Rejected->isTerminal())->toBeTrue()
        ->and(EnrollmentStatus::NoShow->isTerminal())->toBeTrue()
        ->and(EnrollmentStatus::Pending->isTerminal())->toBeFalse()
        ->and(EnrollmentStatus::Confirmed->isTerminal())->toBeFalse()
        ->and(EnrollmentStatus::Waitlisted->isTerminal())->toBeFalse()
        ->and(EnrollmentStatus::CheckedIn->isTerminal())->toBeFalse();
});

test('when enrollment status helpers are evaluated, then is and named checks work', function (): void {
    expect(EnrollmentStatus::Confirmed->isConfirmed())->toBeTrue()
        ->and(EnrollmentStatus::Confirmed->is(EnrollmentStatus::Confirmed))->toBeTrue()
        ->and(EnrollmentStatus::Confirmed->is(EnrollmentStatus::Waitlisted))->toBeFalse()
        ->and(EnrollmentStatus::Waitlisted->isWaitlisted())->toBeTrue()
        ->and(EnrollmentStatus::Pending->is(EnrollmentStatus::Confirmed, EnrollmentStatus::Waitlisted))->toBeFalse();
});

test('when rsvp enrollment status is evaluated, then response message matches status', function (EnrollmentStatus $status, string $expectedKey): void {
    $position = 5;

    expect($status->getResponseMessage($position))->toBe(__($expectedKey, ['position' => $position]));
})->with([
    [EnrollmentStatus::Confirmed, 'events::pages.confirm_presence_success'],
    [EnrollmentStatus::Waitlisted, 'events::pages.waitlist_success'],
]);

test('when response message is requested for unsupported enrollment status, then it fails explicitly', function (): void {
    expect(fn (): string => EnrollmentStatus::Pending->getResponseMessage())
        ->toThrow(EnrollmentException::class);
});
