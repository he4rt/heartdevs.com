<?php

declare(strict_types=1);

use He4rt\Activity\Timeline\Actions\TogglePinPost;
use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can pin their own post', function (): void {
    $user = User::factory()->create();
    $postEntry = PostEntry::factory()->create();

    $timeline = Timeline::factory()
        ->for($user)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $postEntry->id,
        ]);

    resolve(TogglePinPost::class)->handle($user, $timeline);

    expect($timeline->fresh()->pinned)->toBeTrue();
});

test('pinning a post unpins the previously pinned post', function (): void {
    $user = User::factory()->create();

    $firstEntry = PostEntry::factory()->create();
    $firstPost = Timeline::factory()
        ->for($user)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $firstEntry->id,
            'pinned' => true,
        ]);

    $secondEntry = PostEntry::factory()->create();
    $secondPost = Timeline::factory()
        ->for($user)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $secondEntry->id,
        ]);

    resolve(TogglePinPost::class)->handle($user, $secondPost);

    expect($firstPost->fresh()->pinned)->toBeFalse()
        ->and($secondPost->fresh()->pinned)->toBeTrue();
});

test('user can unpin their own post', function (): void {
    $user = User::factory()->create();
    $postEntry = PostEntry::factory()->create();

    $timeline = Timeline::factory()
        ->for($user)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $postEntry->id,
            'pinned' => true,
        ]);

    resolve(TogglePinPost::class)->handle($user, $timeline);

    expect($timeline->fresh()->pinned)->toBeFalse();
});

test('user cannot pin another users post', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $postEntry = PostEntry::factory()->create();

    $timeline = Timeline::factory()
        ->for($owner)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $postEntry->id,
        ]);

    resolve(TogglePinPost::class)->handle($other, $timeline);
})->throws(AuthorizationException::class);
