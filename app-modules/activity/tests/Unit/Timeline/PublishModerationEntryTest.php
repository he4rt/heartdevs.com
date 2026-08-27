<?php

declare(strict_types=1);

use He4rt\Activity\Moderation\Enums\ModerationType;
use He4rt\Activity\Moderation\Models\ModerationEvent;
use He4rt\Activity\Timeline\Timeline;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('publishes a timeline entry for a ban moderation event via observer', function (): void {
    $user = User::factory()->create();
    $moderatorIdentity = ExternalIdentity::factory()->create([
        'model_id' => $user->id,
        'model_type' => (new User)->getMorphClass(),
    ]);

    $event = ModerationEvent::query()->create([
        'external_identity_id' => null,
        'moderator_identity_id' => $moderatorIdentity->id,
        'type' => ModerationType::Ban,
        'reason' => 'Spamming',
        'occurred_at' => now(),
    ]);

    $this->assertDatabaseCount('activity_timeline', 1);

    $timeline = Timeline::query()->where('postable_type', (new ModerationEvent)->getMorphClass())->first();

    expect($timeline)->not->toBeNull()
        ->and($timeline->user_id)->toBe($user->id)
        ->and($timeline->postable_type)->toBe((new ModerationEvent)->getMorphClass())
        ->and($timeline->postable_id)->toBe($event->id);
});

test('publishes a timeline entry for a kick moderation event via observer', function (): void {
    $moderator = User::factory()->create();
    $moderatorIdentity = ExternalIdentity::factory()->create([
        'model_id' => $moderator->id,
        'model_type' => (new User)->getMorphClass(),
    ]);

    $subject = User::factory()->create();
    $subjectIdentity = ExternalIdentity::factory()->create([
        'model_id' => $subject->id,
        'model_type' => (new User)->getMorphClass(),
    ]);

    $event = ModerationEvent::query()->create([
        'external_identity_id' => $subjectIdentity->id,
        'moderator_identity_id' => $moderatorIdentity->id,
        'type' => ModerationType::Kick,
        'reason' => 'Disruptive behavior',
        'occurred_at' => now(),
    ]);

    $this->assertDatabaseCount('activity_timeline', 1);

    $timeline = Timeline::query()->where('postable_type', (new ModerationEvent)->getMorphClass())->first();

    expect($timeline)->not->toBeNull()
        ->and($timeline->user_id)->toBe($moderator->id)
        ->and($timeline->postable_type)->toBe((new ModerationEvent)->getMorphClass());
});

test('does not publish a timeline entry when moderator identity is missing', function (): void {
    $user = User::factory()->create();
    $subjectIdentity = ExternalIdentity::factory()->create([
        'model_id' => $user->id,
        'model_type' => (new User)->getMorphClass(),
    ]);

    ModerationEvent::query()->create([
        'external_identity_id' => $subjectIdentity->id,
        'moderator_identity_id' => null,
        'type' => ModerationType::Kick,
        'reason' => 'Disruptive behavior',
        'occurred_at' => now(),
    ]);

    $this->assertDatabaseCount('activity_timeline', 0);
});

test('does not publish a timeline entry for a warn moderation event', function (): void {
    ModerationEvent::query()->create([
        'external_identity_id' => null,
        'moderator_identity_id' => null,
        'type' => ModerationType::Warn,
        'reason' => 'Minor offense',
        'occurred_at' => now(),
    ]);

    $this->assertDatabaseCount('activity_timeline', 0);
});
