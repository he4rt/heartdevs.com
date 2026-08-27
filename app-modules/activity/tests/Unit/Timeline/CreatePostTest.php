<?php

declare(strict_types=1);

use He4rt\Activity\Timeline\Actions\CreatePost;
use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\DTOs\CreatePostDTO;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('creates a post entry and timeline record', function (): void {
    $timeline = resolve(CreatePost::class)->handle(new CreatePostDTO(
        userId: $this->user->id,
        content: 'Hello **He4rt** community!',
    ));

    expect($timeline)->toBeInstanceOf(Timeline::class)
        ->and($timeline->user_id)->toBe($this->user->id)
        ->and($timeline->postable_type)->toBe((new PostEntry)->getMorphClass())
        ->and($timeline->postable)->toBeInstanceOf(PostEntry::class)
        ->and($timeline->postable->content)->toBe('Hello **He4rt** community!')
        ->and($timeline->root_id)->toBeNull()
        ->and($timeline->parent_id)->toBeNull();

    $this->assertDatabaseCount('activity_post_entries', 1);
    $this->assertDatabaseCount('activity_timeline', 1);
});

test('creates a post with long content succeeds', function (): void {
    $longContent = str_repeat('a', 2_000);

    $timeline = resolve(CreatePost::class)->handle(new CreatePostDTO(
        userId: $this->user->id,
        content: $longContent,
    ));

    expect($timeline->postable->content)->toBe($longContent);
});

test('post creation is atomic — no orphaned PostEntry on Timeline failure', function (): void {
    expect(fn () => resolve(CreatePost::class)->handle(new CreatePostDTO(
        userId: 'not-a-valid-uuid',
        content: 'This should fail',
    )))->toThrow(QueryException::class);

    $this->assertDatabaseCount('activity_post_entries', 0);
});

test('creates a post with empty content is rejected', function (): void {
    resolve(CreatePost::class)->handle(new CreatePostDTO(
        userId: $this->user->id,
        content: '',
    ));
})->throws(InvalidArgumentException::class);
