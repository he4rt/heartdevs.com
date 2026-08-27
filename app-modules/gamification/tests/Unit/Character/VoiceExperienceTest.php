<?php

declare(strict_types=1);

use He4rt\Gamification\Character\Actions\IncrementExperience;
use He4rt\Gamification\Character\Enums\VoiceStatesEnum;
use He4rt\Gamification\Character\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('voice state multipliers', function (VoiceStatesEnum $state, int $expected): void {
    expect($state->getExperienceMultiplier())->toBe($expected);
})->with([
    'disabled gives 0' => [VoiceStatesEnum::Disabled, 0],
    'muted gives 1' => [VoiceStatesEnum::Muted, 1],
    'unmuted gives 3' => [VoiceStatesEnum::Unmuted, 3],
]);

test('voice xp increments by multiplier times level', function (): void {
    $character = Character::factory()->create(['experience' => 4_500]);
    // level 10 at 4500 xp, unmuted = 3 * 10 = 30

    $xp = resolve(IncrementExperience::class)
        ->incrementByVoiceMessage($character->id, VoiceStatesEnum::Unmuted);

    expect($xp)->toBe(30)
        ->and($character->fresh()->experience)->toBe(4_530);
});

test('disabled voice state gives zero xp', function (): void {
    $character = Character::factory()->create(['experience' => 4_500]);

    $xp = resolve(IncrementExperience::class)
        ->incrementByVoiceMessage($character->id, VoiceStatesEnum::Disabled);

    expect($xp)->toBe(0)
        ->and($character->fresh()->experience)->toBe(4_500);
});

test('text message increment works through action', function (): void {
    $character = Character::factory()->create(['experience' => 0]);
    $message = str_repeat('a', 200);

    $xp = resolve(IncrementExperience::class)
        ->incrementByTextMessage($character->id, $message, isSupporter: false);

    // (200 * 0.01) + (1 * 0.1) = 2.1 → (int) 2
    expect($xp)->toBe(2)
        ->and($character->fresh()->experience)->toBe(2);
});

test('text message increment passes supporter flag', function (): void {
    $character = Character::factory()->create(['experience' => 0]);
    $message = str_repeat('a', 200);

    $xp = resolve(IncrementExperience::class)
        ->incrementByTextMessage($character->id, $message, isSupporter: true);

    // non-supporter would be 2, supporter = 4
    expect($xp)->toBe(4)
        ->and($character->fresh()->experience)->toBe(4);
});
