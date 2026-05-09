<?php

declare(strict_types=1);

use He4rt\Activity\Timeline\Delegated\PostEntry;
use He4rt\Activity\Timeline\Queries\TimelineFeed;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('returns only root posts for tenant ordered by newest first', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    $older = Timeline::factory()->for($user)->create([
        'tenant_id' => $tenant->id,
        'postable_type' => (new PostEntry)->getMorphClass(),
        'postable_id' => PostEntry::factory()->create()->id,
        'created_at' => now()->subHour(),
    ]);

    $newer = Timeline::factory()->for($user)->create([
        'tenant_id' => $tenant->id,
        'postable_type' => (new PostEntry)->getMorphClass(),
        'postable_id' => PostEntry::factory()->create()->id,
        'created_at' => now(),
    ]);

    $result = new TimelineFeed($tenant->id)->builder()->simplePaginate(15);

    expect($result)->toHaveCount(2)
        ->and($result->first()->id)->toBe($newer->id);
});

test('excludes replies from feed', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $postEntry = PostEntry::factory()->create();

    $root = Timeline::factory()->for($user)->create([
        'tenant_id' => $tenant->id,
        'postable_type' => (new PostEntry)->getMorphClass(),
        'postable_id' => $postEntry->id,
    ]);

    $replyEntry = PostEntry::factory()->create();
    Timeline::factory()->for($user)->create([
        'tenant_id' => $tenant->id,
        'postable_type' => (new PostEntry)->getMorphClass(),
        'postable_id' => $replyEntry->id,
        'root_id' => $root->id,
        'parent_id' => $root->id,
    ]);

    $result = new TimelineFeed($tenant->id)->builder()->simplePaginate(15);

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe($root->id);
});

test('excludes ignored posts from feed', function (): void {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();

    Timeline::factory()->for($user)->create([
        'tenant_id' => $tenant->id,
        'postable_type' => (new PostEntry)->getMorphClass(),
        'postable_id' => PostEntry::factory()->create()->id,
        'is_ignored' => true,
    ]);

    $visible = Timeline::factory()->for($user)->create([
        'tenant_id' => $tenant->id,
        'postable_type' => (new PostEntry)->getMorphClass(),
        'postable_id' => PostEntry::factory()->create()->id,
    ]);

    $result = new TimelineFeed($tenant->id)->builder()->simplePaginate(15);

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe($visible->id);
});

test('does not show posts from other tenants', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $user = User::factory()->create();

    Timeline::factory()->for($user)->create([
        'tenant_id' => $tenantA->id,
        'postable_type' => (new PostEntry)->getMorphClass(),
        'postable_id' => PostEntry::factory()->create()->id,
    ]);

    Timeline::factory()->for($user)->create([
        'tenant_id' => $tenantB->id,
        'postable_type' => (new PostEntry)->getMorphClass(),
        'postable_id' => PostEntry::factory()->create()->id,
    ]);

    $result = new TimelineFeed($tenantA->id)->builder()->simplePaginate(15);

    expect($result)->toHaveCount(1);
});
