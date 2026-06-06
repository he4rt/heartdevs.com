<?php

declare(strict_types=1);

use He4rt\Gamification\Character\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('zero experience is level 1', function (): void {
    $character = Character::factory()->create(['experience' => 0]);

    expect($character->level)->toBe(1);
});

test('experience at exact threshold gives that level', function (int $level, int $xp): void {
    $character = Character::factory()->create(['experience' => $xp]);

    expect($character->level)->toBe($level);
})->with([
    'level 2 at 120 xp' => [2, 120],
    'level 5 at 1000 xp' => [5, 1000],
    'level 10 at 4500 xp' => [10, 4500],
    'level 25 at 39000 xp' => [25, 39000],
    'level 50 at 400000 xp' => [50, 400000],
]);

test('experience just below threshold stays at previous level', function (int $expectedLevel, int $xp): void {
    $character = Character::factory()->create(['experience' => $xp]);

    expect($character->level)->toBe($expectedLevel);
})->with([
    'level 1 at 119 xp' => [1, 119],
    'level 4 at 999 xp' => [4, 999],
    'level 9 at 4499 xp' => [9, 4499],
]);

test('experience beyond max threshold is still level 50', function (): void {
    $character = Character::factory()->create(['experience' => 999999]);

    expect($character->level)->toBe(50);
});

test('percentage experience at midpoint', function (): void {
    // Level 1: 0, Level 2: 120 → midpoint = 60
    $character = Character::factory()->create(['experience' => 60]);

    expect($character->level)->toBe(1)
        ->and($character->percentage_experience)->toBe(50.0);
});

test('percentage experience at start of level', function (): void {
    $character = Character::factory()->create(['experience' => 120]);

    expect($character->level)->toBe(2)
        ->and($character->percentage_experience)->toBe(0.0);
});

test('percentage experience at max level is 100', function (): void {
    $character = Character::factory()->create(['experience' => 400000]);

    expect($character->level)->toBe(50)
        ->and($character->percentage_experience)->toBe(100.0);
});

test('experience progress shows xp remaining to next level', function (): void {
    // Level 1: 0, Level 2: 120. At 60 xp → 60 remaining
    $character = Character::factory()->create(['experience' => 60]);

    expect($character->experience_progress)->toBe(60);
});
