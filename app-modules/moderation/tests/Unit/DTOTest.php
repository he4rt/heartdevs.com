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
        author: null,
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
        ->and($dto->author)->toBeNull()
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
