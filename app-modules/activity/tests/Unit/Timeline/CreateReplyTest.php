<?php

declare(strict_types=1);

use He4rt\Activity\Timeline\Actions\CreateReply;
use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\DTOs\CreateReplyDTO;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creates a reply to a root post', function (): void {
    $user = User::factory()->create();
    $postEntry = PostEntry::factory()->create();

    $rootPost = Timeline::factory()
        ->for($user)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $postEntry->id,
        ]);

    $replier = User::factory()->create();

    $reply = resolve(CreateReply::class)->handle(new CreateReplyDTO(
        userId: $replier->id,
        parentTimelineId: $rootPost->id,
        content: 'Great post!',
    ));

    expect($reply->parent_id)->toBe($rootPost->id)
        ->and($reply->root_id)->toBe($rootPost->id)
        ->and($reply->postable->content)->toBe('Great post!');
});

test('reply to a reply flattens to root level', function (): void {
    $user = User::factory()->create();
    $postEntry = PostEntry::factory()->create();

    $rootPost = Timeline::factory()
        ->for($user)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $postEntry->id,
        ]);

    $replyEntry = PostEntry::factory()->create();
    $reply = Timeline::factory()
        ->for($user)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $replyEntry->id,
            'root_id' => $rootPost->id,
            'parent_id' => $rootPost->id,
        ]);

    $secondReplier = User::factory()->create();

    $replyToReply = resolve(CreateReply::class)->handle(new CreateReplyDTO(
        userId: $secondReplier->id,
        parentTimelineId: $reply->id,
        content: 'Replying to a reply',
    ));

    expect($replyToReply->root_id)->toBe($rootPost->id)
        ->and($replyToReply->parent_id)->toBe($rootPost->id);
});

test('reply creation is atomic — no orphaned PostEntry on Timeline failure', function (): void {
    $user = User::factory()->create();
    $postEntry = PostEntry::factory()->create();

    $rootPost = Timeline::factory()
        ->for($user)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $postEntry->id,
        ]);

    $initialCount = PostEntry::query()->count();

    expect(fn () => resolve(CreateReply::class)->handle(new CreateReplyDTO(
        userId: 'non-existent-user-id',
        parentTimelineId: $rootPost->id,
        content: 'This should fail',
    )))->toThrow(QueryException::class);

    expect(PostEntry::query()->count())->toBe($initialCount);
});
