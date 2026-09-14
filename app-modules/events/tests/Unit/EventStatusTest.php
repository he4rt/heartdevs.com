<?php

declare(strict_types=1);

use He4rt\Events\Event\Enums\EventStatus;

test('when a draft event is evaluated, then it can transition to published or cancelled', function (): void {
    expect(EventStatus::Draft->canTransitionTo(EventStatus::Published))->toBeTrue()
        ->and(EventStatus::Draft->canTransitionTo(EventStatus::Cancelled))->toBeTrue();
});

test('when a draft event is evaluated, then it cannot transition to completed', function (): void {
    expect(EventStatus::Draft->canTransitionTo(EventStatus::Completed))->toBeFalse();
});

test('when a published event is evaluated, then it can transition to completed or cancelled', function (): void {
    expect(EventStatus::Published->canTransitionTo(EventStatus::Completed))->toBeTrue()
        ->and(EventStatus::Published->canTransitionTo(EventStatus::Cancelled))->toBeTrue();
});

test('when a published event is evaluated, then it cannot transition to draft', function (): void {
    expect(EventStatus::Published->canTransitionTo(EventStatus::Draft))->toBeFalse();
});

test('when a terminal event status is evaluated, then it cannot transition to any state', function (EventStatus $status): void {
    foreach (EventStatus::cases() as $target) {
        expect($status->canTransitionTo($target))->toBeFalse();
    }
})->with([
    EventStatus::Completed,
    EventStatus::Cancelled,
]);

test('when event statuses are evaluated, then terminal states are correctly identified', function (): void {
    expect(EventStatus::Completed->isTerminal())->toBeTrue()
        ->and(EventStatus::Cancelled->isTerminal())->toBeTrue()
        ->and(EventStatus::Draft->isTerminal())->toBeFalse()
        ->and(EventStatus::Published->isTerminal())->toBeFalse();
});
