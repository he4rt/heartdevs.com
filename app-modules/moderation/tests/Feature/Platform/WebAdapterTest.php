<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Platform\Notifications\ModerationActionNotification;
use He4rt\Moderation\Platform\WebModerationAdapter;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();
});

test('returns Web platform', function (): void {
    expect(WebModerationAdapter::make()->platform())->toBe(Platform::Web);
});

test('supports warn, suspend, ban, and content_remove', function (): void {
    $supported = WebModerationAdapter::make()->supports();

    expect($supported)->toContain(ActionType::Warn)
        ->and($supported)->toContain(ActionType::Suspend)
        ->and($supported)->toContain(ActionType::Ban)
        ->and($supported)->toContain(ActionType::ContentRemove)
        ->and($supported)->not->toContain(ActionType::Kick)
        ->and($supported)->not->toContain(ActionType::Mute);
});

test('execute warn sends notification to user', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => 'warn',
        'target_platforms' => ['web'],
        'reason' => 'Inappropriate language',
    ]);

    $result = WebModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue()
        ->and($result->platform)->toBe(Platform::Web)
        ->and($result->platformResponse['action'])->toBe('warn');

    Notification::assertSentTo($user, ModerationActionNotification::class);
});

test('execute suspend updates user and sends notification', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => 'suspend',
        'target_platforms' => ['web'],
        'duration' => '7d',
        'reason' => 'Repeated toxicity',
    ]);

    $result = WebModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue();

    $user->refresh();
    expect($user->suspended_until)->not->toBeNull()
        ->and($user->suspended_until->isFuture())->toBeTrue();

    Notification::assertSentTo($user, ModerationActionNotification::class);
});

test('execute suspend with 24h duration', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => 'suspend',
        'target_platforms' => ['web'],
        'duration' => '24h',
    ]);

    WebModerationAdapter::make()->execute($action, $user);

    $user->refresh();
    expect($user->suspended_until->isFuture())->toBeTrue()
        ->and((int) now()->diffInHours($user->suspended_until, absolute: false))->toBeGreaterThanOrEqual(20);
});

test('execute suspend with 30d duration', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => 'suspend',
        'target_platforms' => ['web'],
        'duration' => '30d',
    ]);

    WebModerationAdapter::make()->execute($action, $user);

    $user->refresh();
    expect($user->suspended_until->isFuture())->toBeTrue()
        ->and((int) now()->diffInDays($user->suspended_until, absolute: false))->toBeBetween(29, 30);
});

test('execute suspend with unknown duration defaults to 7d', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => 'suspend',
        'target_platforms' => ['web'],
        'duration' => 'unknown',
    ]);

    WebModerationAdapter::make()->execute($action, $user);

    $user->refresh();
    expect($user->suspended_until->isFuture())->toBeTrue();
});

test('execute ban updates user and sends notification', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => 'ban',
        'target_platforms' => ['web'],
        'duration' => 'permanent',
        'reason' => 'Spam bot',
    ]);

    $result = WebModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue();

    $user->refresh();
    expect($user->banned_at)->not->toBeNull();

    Notification::assertSentTo($user, ModerationActionNotification::class);
});

test('execute content_remove sends notification', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => 'content_remove',
        'target_platforms' => ['web'],
        'reason' => 'NSFW content',
    ]);

    $result = WebModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue();

    Notification::assertSentTo($user, ModerationActionNotification::class);
});

test('execute with unsupported action type returns success without notification', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create(['author_id' => $user->id]);
    $action = ModerationAction::factory()->create([
        'case_id' => $case->id,
        'action_type' => 'kick',
        'target_platforms' => ['web'],
    ]);

    $result = WebModerationAdapter::make()->execute($action, $user);

    expect($result->success)->toBeTrue();

    Notification::assertNothingSentTo($user);
});

test('ingest creates DTO from raw payload', function (): void {
    $payload = [
        'content_id' => 'post-123',
        'content_type' => 'post',
        'text' => 'Some post content',
        'media_urls' => ['https://example.com/img.jpg'],
        'metadata' => ['url' => '/posts/123'],
        'author_external_id' => 'user-uuid',
    ];

    $dto = WebModerationAdapter::make()->ingest($payload);

    expect($dto)->toBeInstanceOf(ModerationContentDTO::class)
        ->and($dto->contentId)->toBe('post-123')
        ->and($dto->contentType)->toBe('post')
        ->and($dto->sourcePlatform)->toBe(Platform::Web)
        ->and($dto->textContent)->toBe('Some post content')
        ->and($dto->mediaUrls)->toBe(['https://example.com/img.jpg']);
});

test('resolveUser finds user by ID', function (): void {
    $user = User::factory()->create();

    $found = WebModerationAdapter::make()->resolveUser($user->id);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($user->id);
});

test('resolveUser returns null for unknown UUID', function (): void {
    $found = WebModerationAdapter::make()->resolveUser('019dea00-0000-7000-8000-000000000000');

    expect($found)->toBeNull();
});

test('notify sends notification with action context', function (): void {
    $user = User::factory()->create();
    $action = ModerationAction::factory()->create(['reason' => 'Test reason']);

    WebModerationAdapter::make()->notify($user, 'You have been warned', ['action' => $action]);

    Notification::assertSentTo($user, ModerationActionNotification::class);
});
