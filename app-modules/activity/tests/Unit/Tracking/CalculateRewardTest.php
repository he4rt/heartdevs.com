<?php

declare(strict_types=1);

use He4rt\Activity\Tracking\Actions\CalculateReward;
use He4rt\Activity\Tracking\Models\Interaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('calculates reward with engagement snapshot', function (): void {
    $interaction = Interaction::factory()
        ->withEngagement(reactions: 42, comments: 12, bookmarks: 8)
        ->create([
            'coins_min' => 100,
            'coins_max' => 300,
        ]);

    $result = resolve(CalculateReward::class)->handle($interaction, peerReviewBase: 200);

    // reactions bonus: min(42 * 0.5, 25) = 21
    // bookmarks bonus: min(8 * 1.0, 15) = 8
    // comments bonus: min(12 * 2.0, 30) = 24
    // total engagement: 53
    // coins_awarded: min(200 + 53, 300) = 253
    expect($result['coins_awarded'])->toBe(253)
        ->and($result['xp_awarded'])->toBe(253);
});

test('caps engagement bonus at coins max', function (): void {
    $interaction = Interaction::factory()
        ->withEngagement(reactions: 100, comments: 100, bookmarks: 100)
        ->create([
            'coins_min' => 100,
            'coins_max' => 200,
        ]);

    $result = resolve(CalculateReward::class)->handle($interaction, peerReviewBase: 180);

    expect($result['coins_awarded'])->toBe(200);
});

test('uses coins_min when no engagement and auto approved', function (): void {
    $interaction = Interaction::factory()->create([
        'coins_min' => 5,
        'coins_max' => 10,
        'metadata' => null,
    ]);

    $result = resolve(CalculateReward::class)->handle($interaction);

    expect($result['coins_awarded'])->toBe(5);
});
