<?php

declare(strict_types=1);

use He4rt\Events\Enrollment\Enums\EnrollmentStatus;

test('pending can transition to confirmed, rejected, or cancelled', function (): void {
    expect(EnrollmentStatus::Pending->canTransitionTo(EnrollmentStatus::Confirmed))->toBeTrue()
        ->and(EnrollmentStatus::Pending->canTransitionTo(EnrollmentStatus::Rejected))->toBeTrue()
        ->and(EnrollmentStatus::Pending->canTransitionTo(EnrollmentStatus::Cancelled))->toBeTrue();
});

test('pending cannot transition to terminal or mid-flow states', function (): void {
    expect(EnrollmentStatus::Pending->canTransitionTo(EnrollmentStatus::Waitlisted))->toBeFalse()
        ->and(EnrollmentStatus::Pending->canTransitionTo(EnrollmentStatus::CheckedIn))->toBeFalse()
        ->and(EnrollmentStatus::Pending->canTransitionTo(EnrollmentStatus::Attended))->toBeFalse()
        ->and(EnrollmentStatus::Pending->canTransitionTo(EnrollmentStatus::NoShow))->toBeFalse();
});

test('waitlisted can transition to confirmed or cancelled', function (): void {
    expect(EnrollmentStatus::Waitlisted->canTransitionTo(EnrollmentStatus::Confirmed))->toBeTrue()
        ->and(EnrollmentStatus::Waitlisted->canTransitionTo(EnrollmentStatus::Cancelled))->toBeTrue();
});

test('confirmed can transition to checked_in, cancelled, or no_show', function (): void {
    expect(EnrollmentStatus::Confirmed->canTransitionTo(EnrollmentStatus::CheckedIn))->toBeTrue()
        ->and(EnrollmentStatus::Confirmed->canTransitionTo(EnrollmentStatus::Cancelled))->toBeTrue()
        ->and(EnrollmentStatus::Confirmed->canTransitionTo(EnrollmentStatus::NoShow))->toBeTrue();
});

test('checked_in can only transition to attended', function (): void {
    expect(EnrollmentStatus::CheckedIn->canTransitionTo(EnrollmentStatus::Attended))->toBeTrue()
        ->and(EnrollmentStatus::CheckedIn->canTransitionTo(EnrollmentStatus::Confirmed))->toBeFalse()
        ->and(EnrollmentStatus::CheckedIn->canTransitionTo(EnrollmentStatus::Cancelled))->toBeFalse();
});

test('terminal states cannot transition to anything', function (EnrollmentStatus $status): void {
    foreach (EnrollmentStatus::cases() as $target) {
        expect($status->canTransitionTo($target))->toBeFalse();
    }
})->with([
    EnrollmentStatus::Attended,
    EnrollmentStatus::Cancelled,
    EnrollmentStatus::Rejected,
    EnrollmentStatus::NoShow,
]);

test('terminal states are correctly identified', function (): void {
    expect(EnrollmentStatus::Attended->isTerminal())->toBeTrue()
        ->and(EnrollmentStatus::Cancelled->isTerminal())->toBeTrue()
        ->and(EnrollmentStatus::Rejected->isTerminal())->toBeTrue()
        ->and(EnrollmentStatus::NoShow->isTerminal())->toBeTrue()
        ->and(EnrollmentStatus::Pending->isTerminal())->toBeFalse()
        ->and(EnrollmentStatus::Confirmed->isTerminal())->toBeFalse()
        ->and(EnrollmentStatus::Waitlisted->isTerminal())->toBeFalse()
        ->and(EnrollmentStatus::CheckedIn->isTerminal())->toBeFalse();
});
