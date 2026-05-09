<?php

declare(strict_types=1);

use He4rt\Activity\Timeline\Actions\CreatePost;
use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creates a post entry and timeline record', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    $timeline = resolve(CreatePost::class)->handle(
        user: $user,
        tenantId: $tenant->id,
        content: 'Hello **He4rt** community!',
    );

    expect($timeline)->toBeInstanceOf(Timeline::class)
        ->and($timeline->user_id)->toBe($user->id)
        ->and($timeline->tenant_id)->toBe($tenant->id)
        ->and($timeline->postable_type)->toBe('post_entry')
        ->and($timeline->postable)->toBeInstanceOf(PostEntry::class)
        ->and($timeline->postable->content)->toBe('Hello **He4rt** community!')
        ->and($timeline->root_id)->toBeNull()
        ->and($timeline->parent_id)->toBeNull();

    $this->assertDatabaseCount('activity_post_entries', 1);
    $this->assertDatabaseCount('activity_timeline', 1);
});

test('creates a post with empty content is rejected', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    resolve(CreatePost::class)->handle(
        user: $user,
        tenantId: $tenant->id,
        content: '',
    );
})->throws(InvalidArgumentException::class);
