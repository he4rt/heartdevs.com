<?php

declare(strict_types=1);

use He4rt\Activity\Timeline\Actions\CreateReply;
use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\DTOs\CreateReplyDTO;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creates a reply to a root post', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $postEntry = PostEntry::factory()->create();

    $rootPost = Timeline::factory()
        ->for($user)
        ->create([
            'tenant_id' => $tenant->id,
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $postEntry->id,
        ]);

    $replier = User::factory()->create();

    $reply = resolve(CreateReply::class)->handle(new CreateReplyDTO(
        userId: $replier->id,
        tenantId: $tenant->id,
        parentTimelineId: $rootPost->id,
        content: 'Great post!',
    ));

    expect($reply->parent_id)->toBe($rootPost->id)
        ->and($reply->root_id)->toBe($rootPost->id)
        ->and($reply->tenant_id)->toBe($tenant->id)
        ->and($reply->postable->content)->toBe('Great post!');
});

test('reply to a reply flattens to root level', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $postEntry = PostEntry::factory()->create();

    $rootPost = Timeline::factory()
        ->for($user)
        ->create([
            'tenant_id' => $tenant->id,
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $postEntry->id,
        ]);

    $replyEntry = PostEntry::factory()->create();
    $reply = Timeline::factory()
        ->for($user)
        ->create([
            'tenant_id' => $tenant->id,
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $replyEntry->id,
            'root_id' => $rootPost->id,
            'parent_id' => $rootPost->id,
        ]);

    $secondReplier = User::factory()->create();

    $replyToReply = resolve(CreateReply::class)->handle(new CreateReplyDTO(
        userId: $secondReplier->id,
        tenantId: $tenant->id,
        parentTimelineId: $reply->id,
        content: 'Replying to a reply',
    ));

    expect($replyToReply->root_id)->toBe($rootPost->id)
        ->and($replyToReply->parent_id)->toBe($rootPost->id);
});

test('reply creation is atomic — no orphaned PostEntry on Timeline failure', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $postEntry = PostEntry::factory()->create();

    $rootPost = Timeline::factory()
        ->for($user)
        ->create([
            'tenant_id' => $tenant->id,
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $postEntry->id,
        ]);

    $initialCount = PostEntry::query()->count();

    try {
        resolve(CreateReply::class)->handle(new CreateReplyDTO(
            userId: 'non-existent-user-id',
            tenantId: $tenant->id,
            parentTimelineId: $rootPost->id,
            content: 'This should fail',
        ));
    } catch (Throwable) {
    }

    expect(PostEntry::query()->count())->toBe($initialCount);
});
