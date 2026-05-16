<?php

declare(strict_types=1);

use He4rt\Moderation\DTOs\ClassificationResultDTO;
use He4rt\Moderation\DTOs\ExecutionResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\DTOs\SuggestedPenaltyDTO;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;

test('ModerationContentDTO holds all fields', function (): void {
    $dto = new ModerationContentDTO(
        contentId: 'msg-123',
        contentType: 'message',
        sourcePlatform: Platform::Discord,
        authorExternalId: '999888777',
        textContent: 'spam content here',
        mediaUrls: ['https://example.com/img.png'],
        metadata: ['channel_id' => '123', 'guild_id' => '456'],
        snapshot: ['raw' => 'full message data'],
        tenantId: 'tenant-uuid',
    );

    expect($dto->contentId)->toBe('msg-123')
        ->and($dto->contentType)->toBe('message')
        ->and($dto->sourcePlatform)->toBe(Platform::Discord)
        ->and($dto->authorExternalId)->toBe('999888777')
        ->and($dto->textContent)->toBe('spam content here')
        ->and($dto->mediaUrls)->toBe(['https://example.com/img.png'])
        ->and($dto->metadata)->toBe(['channel_id' => '123', 'guild_id' => '456'])
        ->and($dto->snapshot)->toBe(['raw' => 'full message data'])
        ->and($dto->tenantId)->toBe('tenant-uuid');
});

test('ClassificationResultDTO holds scores and primary violation', function (): void {
    $dto = new ClassificationResultDTO(
        scores: ['spam' => 0.92, 'toxicity' => 0.15],
        primary: ViolationType::Spam,
        severity: Severity::High,
        classifierName: 'openai',
        matchedRules: ['rule-uuid-1'],
    );

    expect($dto->scores)->toBe(['spam' => 0.92, 'toxicity' => 0.15])
        ->and($dto->primary)->toBe(ViolationType::Spam)
        ->and($dto->severity)->toBe(Severity::High)
        ->and($dto->classifierName)->toBe('openai')
        ->and($dto->matchedRules)->toBe(['rule-uuid-1']);
});

test('SuggestedPenaltyDTO holds suggestion with reasoning', function (): void {
    $dto = new SuggestedPenaltyDTO(
        action: ActionType::Ban,
        duration: '7d',
        reasoning: '3rd offense in 30 days',
        priorOffenses: 3,
        history: [['type' => 'warn', 'date' => '2026-04-20']],
    );

    expect($dto->action)->toBe(ActionType::Ban)
        ->and($dto->duration)->toBe('7d')
        ->and($dto->reasoning)->toBe('3rd offense in 30 days')
        ->and($dto->priorOffenses)->toBe(3)
        ->and($dto->history)->toHaveCount(1);
});

test('ExecutionResultDTO holds platform result', function (): void {
    $dto = new ExecutionResultDTO(
        platform: Platform::Discord,
        success: true,
        error: null,
        platformResponse: ['ban_id' => '123'],
    );

    expect($dto->platform)->toBe(Platform::Discord)
        ->and($dto->success)->toBeTrue()
        ->and($dto->error)->toBeNull()
        ->and($dto->platformResponse)->toBe(['ban_id' => '123']);
});

test('ExecutionResultDTO captures failure', function (): void {
    $dto = new ExecutionResultDTO(
        platform: Platform::Twitch,
        success: false,
        error: 'User not found on platform',
        platformResponse: [],
    );

    expect($dto->success)->toBeFalse()
        ->and($dto->error)->toBe('User not found on platform');
});

test('ModerationContentDTO can be json_encoded', function (): void {
    $dto = new ModerationContentDTO(
        contentId: 'msg-ser-1',
        contentType: 'message',
        sourcePlatform: Platform::Discord,
        authorExternalId: 'ext-123',
        textContent: 'test serialization',
        mediaUrls: ['https://example.com/file.png'],
        metadata: ['channel_id' => 'ch-1'],
        snapshot: ['text' => 'test serialization'],
        tenantId: 'tenant-1',
    );

    $json = json_encode($dto);

    expect($json)->toBeString();

    $decoded = json_decode($json, true);
    expect($decoded['content_id'])->toBe('msg-ser-1')
        ->and($decoded['source_platform'])->toBe('discord')
        ->and($decoded['author_external_id'])->toBe('ext-123')
        ->and($decoded['text_content'])->toBe('test serialization')
        ->and($decoded['tenant_id'])->toBe('tenant-1')
        ->and($decoded)->not->toHaveKey('author');
});

test('ModerationContentDTO serialized output has no author key', function (): void {
    $dto = new ModerationContentDTO(
        contentId: 'msg-no-author',
        contentType: 'message',
        sourcePlatform: Platform::Web,
        authorExternalId: 'user-456',
        textContent: 'content without author',
        mediaUrls: [],
        metadata: [],
        snapshot: ['text' => 'content without author'],
        tenantId: null,
    );

    $serialized = $dto->jsonSerialize();

    expect($serialized)->not->toHaveKey('author')
        ->and($serialized)->toHaveKey('author_external_id')
        ->and($serialized['author_external_id'])->toBe('user-456');
});

test('ModerationContentDTO fromPlatform produces correct DTO without author', function (): void {
    $dto = ModerationContentDTO::fromPlatform(Platform::Discord, [
        'content_id' => 'msg-fp-1',
        'content_type' => 'message',
        'author_external_id' => 'ext-789',
        'text' => 'platform content',
        'media_urls' => [],
        'metadata' => ['guild_id' => 'g-1'],
        'tenant_id' => 'tenant-2',
    ]);

    expect($dto->contentId)->toBe('msg-fp-1')
        ->and($dto->sourcePlatform)->toBe(Platform::Discord)
        ->and($dto->authorExternalId)->toBe('ext-789')
        ->and($dto->textContent)->toBe('platform content')
        ->and($dto->tenantId)->toBe('tenant-2');
});
