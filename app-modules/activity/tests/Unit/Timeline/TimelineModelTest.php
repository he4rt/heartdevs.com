<?php

declare(strict_types=1);

use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('timeline belongs to user', function (): void {
    $user = User::factory()->create();
    $postEntry = PostEntry::factory()->create();

    $timeline = Timeline::factory()
        ->for($user)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $postEntry->id,
        ]);

    expect($timeline->user->id)->toBe($user->id)
        ->and($timeline->postable)->toBeInstanceOf(PostEntry::class);
});

test('timeline has children and parent for threading', function (): void {
    $user = User::factory()->create();
    $parentEntry = PostEntry::factory()->create();
    $childEntry = PostEntry::factory()->create();

    $parent = Timeline::factory()
        ->for($user)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $parentEntry->id,
        ]);

    $child = Timeline::factory()
        ->for($user)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $childEntry->id,
            'root_id' => $parent->id,
            'parent_id' => $parent->id,
        ]);

    expect($child->parent->id)->toBe($parent->id)
        ->and($child->root->id)->toBe($parent->id)
        ->and($parent->children)->toHaveCount(1)
        ->and($parent->children->first()->id)->toBe($child->id);
});

test('timeline has reactions via HasReactions trait', function (): void {
    $user = User::factory()->create();
    $postEntry = PostEntry::factory()->create();

    $timeline = Timeline::factory()
        ->for($user)
        ->create([
            'postable_type' => (new PostEntry)->getMorphClass(),
            'postable_id' => $postEntry->id,
        ]);

    $timeline->reactions()->create([
        'emoji_key' => '❤️',
        'emoji_name' => 'heart',
        'count' => 1,
        'count_burst' => 0,
        'count_normal' => 1,
    ]);

    expect($timeline->reactions)->toHaveCount(1)
        ->and($timeline->reactions->first()->emoji_key)->toBe('❤️');
});
