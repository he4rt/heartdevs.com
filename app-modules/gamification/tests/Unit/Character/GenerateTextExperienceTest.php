<?php

declare(strict_types=1);

use He4rt\Gamification\Character\Models\Character;

test('empty message returns minimum 1 xp', function (): void {
    expect(Character::generateTextExperience('', 1, false))->toBe(1);
});

test('short messages at level 1 return minimum 1 xp', function (string $message): void {
    expect(Character::generateTextExperience($message, 1, false))->toBe(1);
})->with([
    'hello' => ['hello'],
    '50 chars' => [str_repeat('a', 50)],
    '89 chars' => [str_repeat('a', 89)],
]);

test('90 chars at level 1 still gives 1 xp from formula', function (): void {
    $message = str_repeat('a', 90);

    // (90 * 0.01) + (1 * 0.1) = 0.9 + 0.1 = 1.0 → max(1, 1) = 1
    expect(Character::generateTextExperience($message, 1, false))->toBe(1);
});

test('xp scales with message length', function (int $length, int $level, int $expectedXp): void {
    $message = str_repeat('a', $length);

    expect(Character::generateTextExperience($message, $level, false))->toBe($expectedXp);
})->with([
    '100 chars, level 1' => [100, 1, 1],
    '200 chars, level 1' => [200, 1, 2],
    '500 chars, level 1' => [500, 1, 5],
    '100 chars, level 10' => [100, 10, 2],
    '100 chars, level 50' => [100, 50, 6],
    '500 chars, level 50' => [500, 50, 10],
]);

test('supporter doubles xp', function (): void {
    $message = str_repeat('a', 200);

    $regularXp = Character::generateTextExperience($message, 10, false);
    $supporterXp = Character::generateTextExperience($message, 10, true);

    expect($supporterXp)->toBe($regularXp * 2);
});

test('supporter gets minimum 2 xp for short messages', function (): void {
    expect(Character::generateTextExperience('hi', 1, true))->toBe(2);
});

test('multibyte characters count by character not byte', function (): void {
    $asciiMessage = str_repeat('a', 100);
    $mbMessage = str_repeat('ã', 100);

    expect(Character::generateTextExperience($mbMessage, 1, false))
        ->toBe(Character::generateTextExperience($asciiMessage, 1, false));
});
