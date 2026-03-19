<?php

declare(strict_types=1);

use He4rt\Activity\Tracking\Actions\ApproveInteraction;
use He4rt\Activity\Tracking\Enums\ActivityStatus;
use He4rt\Activity\Tracking\Events\InteractionApproved;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('approves interaction and credits economy', function (): void {
    Event::fake([InteractionApproved::class]);

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $character = Character::factory()->recycle($user)->recycle($tenant)->create(['experience' => 500]);

    $interaction = Interaction::factory()
        ->withEngagement(reactions: 42, bookmarks: 8, comments: 12)
        ->recycle($character)
        ->recycle($tenant)
        ->create([
            'coins_min' => 100,
            'coins_max' => 300,
            'status' => ActivityStatus::Pending,
        ]);

    $result = resolve(ApproveInteraction::class)->handle($interaction, peerReviewBase: 200);

    expect($result->status)->toBe(ActivityStatus::Approved)
        ->and($result->reviewed_at)->not->toBeNull()
        ->and($result->coins_awarded)->toBe(253);

    $wallet = $character->fresh()->wallets()->first();
    expect($wallet->balance)->toBe(253);

    Event::assertDispatched(InteractionApproved::class);
});
