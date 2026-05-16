<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Events\CaseCreated;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Classification\Jobs\ClassifyAndRoute;
use He4rt\Moderation\Classification\Jobs\ScreenContent;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Pipeline\SubmitForModeration;
use He4rt\Moderation\Rules\ModerationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('rule match creates case with rule data and dispatches ClassifyAndRoute', function (): void {
    Queue::fake();
    Event::fake([CaseCreated::class]);

    ModerationRule::query()->create([
        'name' => 'Spam keywords',
        'type' => 'keyword',
        'pattern' => 'buy followers',
        'violation_type' => 'spam',
        'severity' => 'high',
        'action_on_match' => 'ban',
        'is_active' => true,
    ]);

    $user = User::factory()->create();
    ExternalIdentity::factory()->create([
        'model_type' => (new User)->getMorphClass(),
        'model_id' => $user->id,
        'provider' => IdentityProvider::Discord,
        'external_account_id' => 'discord-ext-123',
    ]);

    $dto = new ModerationContentDTO(
        contentId: 'msg-100',
        contentType: 'message',
        sourcePlatform: Platform::Discord,
        authorExternalId: 'discord-ext-123',
        textContent: 'buy followers now at spam.com',
        mediaUrls: [],
        metadata: [],
        snapshot: ['text' => 'buy followers now at spam.com'],
        tenantId: null,
    );

    $action = resolve(SubmitForModeration::class);
    $case = $action->execute($dto, CaseSource::AutoDetect);

    expect($case)->toBeInstanceOf(ModerationCase::class)
        ->and($case->content_id)->toBe('msg-100')
        ->and($case->status)->toBe(CaseStatus::Pending)
        ->and($case->classifier_version)->toBe('rules')
        ->and($case->ai_scores)->toHaveKey('spam')
        ->and($case->suggested_action)->not->toBeNull()
        ->and($case->author_id)->toBe($user->id);

    Event::assertDispatched(CaseCreated::class);
    Queue::assertPushed(ClassifyAndRoute::class);
    Queue::assertNotPushed(ScreenContent::class);
});

test('no rule match dispatches ScreenContent and returns null', function (): void {
    Queue::fake();

    $dto = new ModerationContentDTO(
        contentId: 'msg-200',
        contentType: 'message',
        sourcePlatform: Platform::Discord,
        authorExternalId: '',
        textContent: 'hello everyone, how are you?',
        mediaUrls: [],
        metadata: [],
        snapshot: ['text' => 'hello everyone, how are you?'],
        tenantId: null,
    );

    $action = resolve(SubmitForModeration::class);
    $result = $action->execute($dto, CaseSource::AutoDetect);

    expect($result)->toBeNull();
    expect(ModerationCase::query()->count())->toBe(0);

    Queue::assertPushed(ScreenContent::class);
    Queue::assertNotPushed(ClassifyAndRoute::class);
});

test('empty text with no matching rules dispatches ScreenContent', function (): void {
    Queue::fake();

    $dto = new ModerationContentDTO(
        contentId: 'msg-300',
        contentType: 'message',
        sourcePlatform: Platform::Web,
        authorExternalId: '',
        textContent: '',
        mediaUrls: [],
        metadata: [],
        snapshot: ['text' => ''],
        tenantId: null,
    );

    $action = resolve(SubmitForModeration::class);
    $result = $action->execute($dto, CaseSource::UserReport);

    expect($result)->toBeNull();

    Queue::assertPushed(ScreenContent::class);
});
