<?php

declare(strict_types=1);

namespace He4rt\Gamification\Tests\Feature;

use He4rt\Events\Actions\ProcessManualOverrideAction;
use He4rt\Events\Actions\ProcessVerifiedAttendanceAction;
use He4rt\Events\Actions\VerifyAttendanceAction;
use He4rt\Events\Enums\AttendingStatusEnum;
use He4rt\Events\Jobs\ResetUnverifiedStreaksJob;
use He4rt\Events\Models\EventModel;
use He4rt\Gamification\Character\Actions\CalculateStreakMultiplierAction;
use He4rt\Gamification\Character\Actions\IncrementExperience;
use He4rt\Gamification\Character\Actions\IncrementStreakAction;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\User\Models\User;

it('increments streak and applies multiplier on attendance verification', function (): void {
    // Setup
    $user = User::factory()->create();
    $character = Character::factory()->create([
        'user_id' => $user->id,
        'streak' => 5,
    ]);
    $event = EventModel::factory()->create(['xp_value' => 200]);

    $action = new ProcessVerifiedAttendanceAction(
        new VerifyAttendanceAction(),
        new IncrementStreakAction(),
        new CalculateStreakMultiplierAction(),
        new IncrementExperience(),
    );

    $action->execute($event, $user->id);

    $character->refresh();
    expect($character->streak)->toBe(6);
    expect($character->experience)->toBe(250);
});

it('resets streak for users who pre-confirmed but did not show up', function (): void {
    $event = EventModel::factory()->create([
        'end_at' => now()->subHours(2),
        'active' => true,
    ]);

    $user = User::factory()->create();
    $character = Character::factory()->create([
        'user_id' => $user->id,
        'streak' => 5,
    ]);

    $event->attendees()->attach($user->id, [
        'status' => AttendingStatusEnum::Attending,
        'verified_at' => null,
    ]);

    dispatch_sync(new ResetUnverifiedStreaksJob($event->id));

    $character->refresh();
    expect($character->streak)->toBe(0);
});

it('does not change streak for users who did not pre-confirm', function (): void {
    $event = EventModel::factory()->create([
        'end_at' => now()->subHours(2),
    ]);

    $user = User::factory()->create();
    $character = Character::factory()->create([
        'user_id' => $user->id,
        'streak' => 5,
    ]);

    expect($event->attendees()->where('user_id', $user->id)->exists())->toBeFalse();

    dispatch_sync(new ResetUnverifiedStreaksJob($event->id));

    $character->refresh();
    expect($character->streak)->toBe(5);
});

it('manual override does not affect streak and awards base XP', function (): void {
    $user = User::factory()->create();
    $character = Character::factory()->create([
        'user_id' => $user->id,
        'streak' => 5,
        'experience' => 1000,
    ]);
    $event = EventModel::factory()->create(['xp_value' => 200]);

    $action = new ProcessManualOverrideAction(
        new IncrementExperience(),
    );

    $action->execute($event, $user->id);

    $character->refresh();
    expect($character->experience)->toBe(1200);
    expect($character->streak)->toBe(5);
});

it('does not affect streaks when event is cancelled', function (): void {
    $event = EventModel::factory()->create([
        'end_at' => now()->subHours(2),
        'active' => false,
    ]);

    $user = User::factory()->create();
    $character = Character::factory()->create([
        'user_id' => $user->id,
        'streak' => 5,
    ]);

    $event->attendees()->attach($user->id, [
        'status' => AttendingStatusEnum::Attending,
        'verified_at' => null,
    ]);

    dispatch_sync(new ResetUnverifiedStreaksJob($event->id));

    $character->refresh();
    expect($character->streak)->toBe(5);
});
