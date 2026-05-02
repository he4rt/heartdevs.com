<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Actions\SubmitReport;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Cases\Models\ModerationReport;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\ViolationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('SubmitReport creates case and runs pipeline', function (): void {
    Http::fake(['api.openai.com/*' => Http::response([
        'results' => [['flagged' => false, 'categories' => [], 'category_scores' => ['harassment' => 0.01]]],
    ])]);

    $reporter = User::factory()->create();
    $author = User::factory()->create();

    $action = resolve(SubmitReport::class);
    $case = $action->handle(
        reporter: $reporter,
        contentDTO: new ModerationContentDTO(
            contentId: 'msg-report-test',
            contentType: 'message',
            sourcePlatform: Platform::Discord,
            authorExternalId: 'ext-id',
            author: $author,
            textContent: 'some bad content',
            mediaUrls: [],
            metadata: [],
            snapshot: ['text' => 'some bad content'],
            tenantId: null,
        ),
        reason: ViolationType::Toxicity,
        details: 'This user is being toxic',
        platform: Platform::Discord,
    );

    expect($case)->toBeInstanceOf(ModerationCase::class)
        ->and($case->source)->toBe(CaseSource::UserReport)
        ->and($case->ai_scores)->not->toBeNull();

    $this->assertDatabaseHas('moderation_reports', [
        'case_id' => $case->id,
        'reporter_id' => $reporter->id,
        'reason' => 'toxicity',
    ]);
});

test('SubmitReport deduplicates reports to same content', function (): void {
    Http::fake(['api.openai.com/*' => Http::response([
        'results' => [['flagged' => true, 'categories' => ['harassment' => true], 'category_scores' => ['harassment' => 0.85]]],
    ])]);

    $author = User::factory()->create();
    $reporter1 = User::factory()->create();
    $reporter2 = User::factory()->create();

    $action = resolve(SubmitReport::class);

    $dto = new ModerationContentDTO(
        contentId: 'msg-dedup',
        contentType: 'message',
        sourcePlatform: Platform::Discord,
        authorExternalId: 'ext-id',
        author: $author,
        textContent: 'bad stuff',
        mediaUrls: [],
        metadata: [],
        snapshot: ['text' => 'bad stuff'],
        tenantId: null,
    );

    $case1 = $action->handle($reporter1, $dto, ViolationType::Spam, null, Platform::Discord);
    $case2 = $action->handle($reporter2, $dto, ViolationType::Spam, null, Platform::Discord);

    expect($case1->id)->toBe($case2->id);
    expect(ModerationReport::query()->where('case_id', $case1->id)->count())->toBe(2);

    $case1->refresh();
    expect($case1->priority)->toBeGreaterThan(50);
});
