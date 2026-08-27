<?php

declare(strict_types=1);

use He4rt\Activity\Timeline\Actions\DeleteReply;
use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner can delete their own reply', function (): void {
    $user = User::factory()->create();

    $rootEntry = PostEntry::factory()->create();
    $rootPost = Timeline::factory()
        ->for($user)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $rootEntry->id,
        ]);

    $replier = User::factory()->create();
    $replyEntry = PostEntry::factory()->create(['content' => 'A reply']);
    $reply = Timeline::factory()
        ->for($replier)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $replyEntry->id,
            'root_id' => $rootPost->id,
            'parent_id' => $rootPost->id,
        ]);

    resolve(DeleteReply::class)->handle($replier, $reply);

    expect(Timeline::query()->find($reply->id))->toBeNull()
        ->and(PostEntry::query()->find($replyEntry->id))->toBeNull();
});

test('cannot delete another users reply', function (): void {
    $user = User::factory()->create();

    $rootEntry = PostEntry::factory()->create();
    $rootPost = Timeline::factory()
        ->for($user)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $rootEntry->id,
        ]);

    $replier = User::factory()->create();
    $replyEntry = PostEntry::factory()->create();
    $reply = Timeline::factory()
        ->for($replier)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $replyEntry->id,
            'root_id' => $rootPost->id,
            'parent_id' => $rootPost->id,
        ]);

    $otherUser = User::factory()->create();

    resolve(DeleteReply::class)->handle($otherUser, $reply);
})->throws(AuthorizationException::class);

test('cannot delete a root post via delete reply', function (): void {
    $user = User::factory()->create();

    $rootEntry = PostEntry::factory()->create();
    $rootPost = Timeline::factory()
        ->for($user)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $rootEntry->id,
        ]);

    resolve(DeleteReply::class)->handle($user, $rootPost);
})->throws(AuthorizationException::class);
