<?php

declare(strict_types=1);

use He4rt\Activity\Tracking\Enums\ActivityStatus;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Models\Interaction;
use He4rt\Contents\Articles\Events\ArticlePublished;
use He4rt\Contents\Database\Factories\ContentEntryFactory;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\User\Models\User;

test('creates an interaction when the article author has a character', function (): void {
    $user = User::factory()->create();
    $character = Character::factory()->create(['user_id' => $user->id]);

    $entry = ContentEntryFactory::new()->authoredBy($user)->create([
        'external_id' => '123',
    ]);

    event(new ArticlePublished($entry->fresh()));

    $interaction = Interaction::query()
        ->where('source_type', 'content_entry')
        ->where('source_id', $entry->id)
        ->first();

    expect($interaction)->not->toBeNull()
        ->and($interaction->external_ref)->toBe('devto:article:123')
        ->and($interaction->type)->toBe(ActivityType::Article)
        ->and($interaction->status)->toBe(ActivityStatus::Pending)
        ->and($interaction->character_id)->toBe($character->id);
});

test('skips tracking and never creates a character when the author has none', function (): void {
    $user = User::factory()->create();

    $entry = ContentEntryFactory::new()->authoredBy($user)->create([
        'external_id' => '456',
    ]);

    event(new ArticlePublished($entry->fresh()));

    expect(Interaction::query()->where('source_id', $entry->id)->exists())->toBeFalse()
        ->and(Character::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

test('deduplicates by external_ref when the event fires twice', function (): void {
    $user = User::factory()->create();
    Character::factory()->create(['user_id' => $user->id]);

    $entry = ContentEntryFactory::new()->authoredBy($user)->create([
        'external_id' => '789',
    ]);

    $fresh = $entry->fresh();

    event(new ArticlePublished($fresh));
    event(new ArticlePublished($fresh));

    expect(Interaction::query()->where('external_ref', 'devto:article:789')->count())->toBe(1);
});
