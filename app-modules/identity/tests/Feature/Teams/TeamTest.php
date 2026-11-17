<?php

declare(strict_types=1);

use He4rt\Identity\Teams\Team;
use He4rt\Identity\Teams\TeamStatus;
use He4rt\Identity\Users\User;

test('team factory creates valid team', function (): void {
    $team = Team::factory()->create();
    expect($team)->toBeInstanceOf(Team::class)
        ->and($team->status)->toBe(TeamStatus::Active)
        ->and($team->owner_id)->not->toBeNull()
        ->and($team->name)->not->toBeEmpty();
});

test('team has owner relation', function (): void {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['owner_id' => $owner->id]);

    expect($team->owner->id)->toBe($owner->id);
});

test('team has members relation', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->members()->attach($user);

    expect($team->members->first()->id)->toBe($user->id);
});
