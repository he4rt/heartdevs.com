<?php

declare(strict_types=1);

use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Support\Facades\Http;

test('syncs articles and creates interactions for linked authors', function (): void {
    Http::fake([
        '*/articles?*page=1*' => Http::response([
            [
                'id' => 101,
                'title' => 'PHP is awesome',
                'url' => 'https://dev.to/linked_user/php-is-awesome',
                'published_at' => '2026-03-15T10:00:00Z',
                'created_at' => '2026-03-15T09:00:00Z',
                'tag_list' => ['php', 'laravel'],
                'public_reactions_count' => 10,
                'comments_count' => 3,
                'user' => ['username' => 'linked_user'],
            ],
            [
                'id' => 102,
                'title' => 'Unlinked article',
                'url' => 'https://dev.to/unknown_user/unlinked',
                'published_at' => '2026-03-16T10:00:00Z',
                'created_at' => '2026-03-16T09:00:00Z',
                'tag_list' => ['go'],
                'public_reactions_count' => 5,
                'comments_count' => 1,
                'user' => ['username' => 'unknown_user'],
            ],
        ]),
        '*/articles?*page=2*' => Http::response([]),
        '*/articles/101' => Http::response([
            'id' => 101,
            'public_reactions_count' => 10,
            'comments_count' => 3,
            'reading_list_count' => 2,
        ]),
    ]);

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $character = Character::factory()->recycle($user)->recycle($tenant)->create();

    ExternalIdentity::factory()->create([
        'tenant_id' => $tenant->id,
        'model_type' => User::class,
        'model_id' => $user->id,
        'provider' => IdentityProvider::DevTo,
        'metadata' => ['email' => 'linked@example.com', 'username' => 'linked_user'],
    ]);

    $this->artisan('devto:sync-articles')
        ->assertSuccessful();

    expect(Interaction::query()->count())->toBe(1);

    $interaction = Interaction::query()->first();
    expect($interaction->type)->toBe(ActivityType::Article)
        ->and($interaction->external_ref)->toBe('devto:article:101')
        ->and($interaction->metadata['title'])->toBe('PHP is awesome')
        ->and($interaction->metadata['engagement_snapshot']['reactions'])->toBe(10);
});

test('updates engagement for existing interactions without creating duplicates', function (): void {
    Http::fake([
        '*/articles?*page=1*' => Http::response([
            [
                'id' => 201,
                'title' => 'Existing article',
                'url' => 'https://dev.to/author/existing',
                'published_at' => '2026-03-10T10:00:00Z',
                'created_at' => '2026-03-10T09:00:00Z',
                'tag_list' => ['php'],
                'public_reactions_count' => 50,
                'comments_count' => 10,
                'user' => ['username' => 'author'],
            ],
        ]),
        '*/articles?*page=2*' => Http::response([]),
        '*/articles/201' => Http::response([
            'id' => 201,
            'public_reactions_count' => 50,
            'comments_count' => 10,
            'reading_list_count' => 5,
        ]),
    ]);

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create();
    $character = Character::factory()->recycle($user)->recycle($tenant)->create();

    ExternalIdentity::factory()->create([
        'tenant_id' => $tenant->id,
        'model_type' => User::class,
        'model_id' => $user->id,
        'provider' => IdentityProvider::DevTo,
        'metadata' => ['email' => 'author@example.com', 'username' => 'author'],
    ]);

    Interaction::factory()->recycle($character)->recycle($tenant)->create([
        'external_ref' => 'devto:article:201',
        'metadata' => [
            'engagement_snapshot' => ['reactions' => 20, 'comments' => 5, 'bookmarks' => 1],
        ],
    ]);

    $this->artisan('devto:sync-articles')
        ->assertSuccessful();

    expect(Interaction::query()->count())->toBe(1);

    $interaction = Interaction::query()->first();
    expect($interaction->metadata['engagement_snapshot']['reactions'])->toBe(50)
        ->and($interaction->metadata['engagement_snapshot']['bookmarks'])->toBe(5);
});
