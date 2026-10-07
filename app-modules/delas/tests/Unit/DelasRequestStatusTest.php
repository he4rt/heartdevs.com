<?php

declare(strict_types=1);

use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;

test('pendente só vira aprovada ou rejeitada', function (DelasRequestStatus $target, bool $allowed): void {
    expect(DelasRequestStatus::Pending->canTransitionTo($target))->toBe($allowed);
})->with([
    [DelasRequestStatus::Approved, true],
    [DelasRequestStatus::Rejected, true],
    [DelasRequestStatus::Revoked, false],
    [DelasRequestStatus::Pending, false],
]);

test('aprovada só vira removida', function (DelasRequestStatus $target, bool $allowed): void {
    expect(DelasRequestStatus::Approved->canTransitionTo($target))->toBe($allowed);
})->with([
    [DelasRequestStatus::Revoked, true],
    [DelasRequestStatus::Pending, false],
    [DelasRequestStatus::Rejected, false],
    [DelasRequestStatus::Approved, false],
]);

test('rejeitada e removida são finais', function (DelasRequestStatus $from): void {
    foreach (DelasRequestStatus::cases() as $target) {
        expect($from->canTransitionTo($target))->toBeFalse();
    }
})->with([DelasRequestStatus::Rejected, DelasRequestStatus::Revoked]);

test('só pendente e aprovada ocupam o lugar da pessoa', function (): void {
    expect(DelasRequestStatus::activeValues())->toBe(['pending', 'approved']);
});
