<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Profile\Models\Profile;

test('profile is created when a user is created', function (): void {
    $user = User::factory()->create([
        'username' => 'danielhe4rt',
    ]);

    $profile = Profile::query()
        ->whereBelongsTo($user)
        ->sole();

    expect($profile->nickname)->toBeNull()
        ->and($profile->headline)->toBeNull()
        ->and($profile->available_for_proposals)->toBeFalse();
});

test('profile is not duplicated when it already exists', function (): void {
    $user = User::factory()->create([
        'username' => 'danielhe4rt',
    ]);

    $existingProfile = Profile::query()->whereBelongsTo($user)->sole();
    $existingProfile->update(['headline' => 'Existing headline']);

    Profile::ensureExists((string) $user->id);

    expect(Profile::query()
        ->whereBelongsTo($user)
        ->count())->toBe(1)
        ->and($existingProfile->fresh()->headline)->toBe('Existing headline');
});

test('factory creates a valid profile and supports optional states', function (): void {
    $user = User::factory()->create();
    // UserObserver already created a blank profile for this user; replace it
    // so the factory's own insert does not collide with the unique user_id.
    Profile::query()->where('user_id', $user->id)->delete();

    $profile = Profile::factory()->complete()->create(['user_id' => $user->id]);

    expect($profile->user_id)->not->toBeNull()
        ->and($profile->nickname)->not->toBeNull()
        ->and($profile->headline)->not->toBeNull()
        ->and($profile->available_for_proposals)->toBeTrue();
});
