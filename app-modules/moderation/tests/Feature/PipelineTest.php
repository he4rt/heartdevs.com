<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Classification\Jobs\ClassifyContent;
use He4rt\Moderation\Classification\Jobs\IngestContent;
use He4rt\Moderation\Classification\Jobs\RouteDecision;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Rules\ModerationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('IngestContent creates a ModerationCase from DTO', function (): void {
    $user = User::factory()->create();
    $dto = new ModerationContentDTO(
        contentId: 'msg-999',
        contentType: 'message',
        sourcePlatform: Platform::Discord,
        authorExternalId: '12345',
        author: $user,
        textContent: 'some spam content',
        mediaUrls: [],
        metadata: ['channel_id' => 'ch-1'],
        snapshot: ['text' => 'some spam content'],
        tenantId: null,
    );

    $job = new IngestContent($dto, CaseSource::UserReport);
    $case = $job->handle();

    expect($case)->toBeInstanceOf(ModerationCase::class)
        ->and($case->content_id)->toBe('msg-999')
        ->and($case->content_type)->toBe('message')
        ->and($case->source_platform)->toBe(Platform::Discord)
        ->and($case->source)->toBe(CaseSource::UserReport)
        ->and($case->status)->toBe(CaseStatus::Pending)
        ->and($case->author_id)->toBe($user->id)
        ->and($case->content_snapshot)->toBe(['text' => 'some spam content']);
});

test('ClassifyContent updates case with AI scores', function (): void {
    ModerationRule::query()->create([
        'name' => 'Spam test',
        'type' => 'keyword',
        'pattern' => 'buy followers',
        'violation_type' => 'spam',
        'severity' => 'high',
        'action_on_match' => 'warn',
        'is_active' => true,
    ]);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'results' => [['flagged' => false, 'categories' => [], 'category_scores' => ['harassment' => 0.1]]],
        ]),
    ]);

    $case = ModerationCase::factory()->create([
        'content_snapshot' => ['text' => 'buy followers now'],
    ]);

    $job = new ClassifyContent($case);
    $job->handle();

    $case->refresh();
    expect($case->ai_scores)->toHaveKey('spam')
        ->and($case->ai_scores['spam'])->toBeGreaterThanOrEqual(0.9)
        ->and($case->violation_type->value)->toBe('spam')
        ->and($case->severity->value)->toBe('high');
});

test('RouteDecision flags case when score exceeds threshold', function (): void {
    $user = User::factory()->create();
    $case = ModerationCase::factory()->create([
        'ai_scores' => ['spam' => 0.85],
        'violation_type' => 'spam',
        'severity' => 'high',
        'status' => 'pending',
        'priority' => 50,
        'author_id' => $user->id,
    ]);

    $job = new RouteDecision($case);
    $job->handle();

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::Pending)
        ->and($case->priority)->toBeGreaterThan(50)
        ->and($case->suggested_action)->not->toBeNull();
});

test('RouteDecision dismisses case when all scores below threshold', function (): void {
    $case = ModerationCase::factory()->create([
        'ai_scores' => ['spam' => 0.1, 'toxicity' => 0.05],
        'status' => 'pending',
    ]);

    $job = new RouteDecision($case);
    $job->handle();

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::Dismissed);
});

test('full pipeline flow: ingest -> classify -> route', function (): void {
    ModerationRule::query()->create([
        'name' => 'Spam URLs',
        'type' => 'keyword',
        'pattern' => 'free followers',
        'violation_type' => 'spam',
        'severity' => 'high',
        'action_on_match' => 'ban',
        'is_active' => true,
    ]);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'results' => [['flagged' => false, 'categories' => [], 'category_scores' => ['harassment' => 0.02]]],
        ]),
    ]);

    $user = User::factory()->create();
    $dto = new ModerationContentDTO(
        contentId: 'msg-full-test',
        contentType: 'message',
        sourcePlatform: Platform::Discord,
        authorExternalId: 'ext-1',
        author: $user,
        textContent: 'Get free followers at spam.com',
        mediaUrls: [],
        metadata: [],
        snapshot: ['text' => 'Get free followers at spam.com'],
        tenantId: null,
    );

    $case = new IngestContent($dto, CaseSource::AutoDetect)->handle();
    new ClassifyContent($case)->handle();
    $case->refresh();
    new RouteDecision($case)->handle();

    $case->refresh();
    expect($case->status)->toBe(CaseStatus::Pending)
        ->and($case->ai_scores['spam'])->toBeGreaterThanOrEqual(0.9)
        ->and($case->suggested_action)->not->toBeNull()
        ->and($case->priority)->toBeGreaterThan(50);
});
