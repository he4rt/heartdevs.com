<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Moderation\Cases\Actions\SubmitReport;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Cases\Models\ModerationReport;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\Moderation\Rules\ModerationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::fake(['api.openai.com/*' => Http::response([
        'results' => [['flagged' => false, 'categories' => [], 'category_scores' => ['harassment' => 0.01]]],
    ])]);
});

function makeReportDTO(string $contentId = 'msg-1', string $text = 'test'): ModerationContentDTO
{
    return new ModerationContentDTO(
        contentId: $contentId,
        contentType: 'message',
        sourcePlatform: Platform::Discord,
        authorExternalId: 'ext-1',
        textContent: $text,
        mediaUrls: [],
        metadata: [],
        snapshot: ['text' => $text],
        tenantId: null,
    );
}

test('creates a new case with report and runs pipeline', function (): void {
    $reporter = User::factory()->create();

    $case = resolve(SubmitReport::class)->handle(
        reporter: $reporter,
        contentDTO: makeReportDTO(),
        reason: ViolationType::Spam,
        details: 'Obvious spam',
        platform: Platform::Discord,
    );

    expect($case)->toBeInstanceOf(ModerationCase::class)
        ->and($case->source)->toBe(CaseSource::UserReport)
        ->and($case->ai_scores)->not->toBeNull()
        ->and($case->reports)->toHaveCount(1)
        ->and($case->reports->first()->reporter_id)->toBe($reporter->id);
});

test('deduplicates reports on same content into existing case', function (): void {
    ModerationRule::query()->create([
        'name' => 'trigger',
        'type' => 'keyword',
        'pattern' => 'bad word',
        'violation_type' => 'spam',
        'severity' => 'high',
        'action_on_match' => 'warn',
        'is_active' => true,
    ]);

    $reporter1 = User::factory()->create();
    $reporter2 = User::factory()->create();
    $dto = makeReportDTO('same-msg', 'bad word here');

    $action = resolve(SubmitReport::class);
    $case1 = $action->handle($reporter1, $dto, ViolationType::Spam, null, Platform::Discord);
    $case2 = $action->handle($reporter2, $dto, ViolationType::Spam, 'also spam', Platform::Discord);

    expect($case1->id)->toBe($case2->id);
    expect(ModerationReport::query()->where('case_id', $case1->id)->count())->toBe(2);

    $case1->refresh();
    expect($case1->priority)->toBeGreaterThan(50);
});

test('does not dedup against resolved cases', function (): void {
    $reporter = User::factory()->create();
    $author = User::factory()->create();

    $resolvedCase = ModerationCase::factory()->create([
        'content_id' => 'msg-resolved',
        'content_type' => 'message',
        'status' => CaseStatus::Resolved,
        'author_id' => $author->id,
    ]);

    $dto = makeReportDTO('msg-resolved', 'same content');
    $newCase = resolve(SubmitReport::class)->handle($reporter, $dto, ViolationType::Toxicity, null, Platform::Discord);

    expect($newCase->id)->not->toBe($resolvedCase->id);
});

test('does not dedup against dismissed cases', function (): void {
    $reporter = User::factory()->create();
    $author = User::factory()->create();

    $dismissedCase = ModerationCase::factory()->create([
        'content_id' => 'msg-dismissed',
        'content_type' => 'message',
        'status' => CaseStatus::Dismissed,
        'author_id' => $author->id,
    ]);

    $dto = makeReportDTO('msg-dismissed', 'content');
    $newCase = resolve(SubmitReport::class)->handle($reporter, $dto, ViolationType::Spam, null, Platform::Web);

    expect($newCase->id)->not->toBe($dismissedCase->id);
});

test('stores report details and platform correctly', function (): void {
    $reporter = User::factory()->create();

    $case = resolve(SubmitReport::class)->handle(
        reporter: $reporter,
        contentDTO: makeReportDTO(),
        reason: ViolationType::Harassment,
        details: 'Detailed description of harassment',
        platform: Platform::Twitch,
    );

    $report = $case->reports->first();
    expect($report->reason)->toBe(ViolationType::Harassment)
        ->and($report->details)->toBe('Detailed description of harassment')
        ->and($report->platform)->toBe(Platform::Twitch);
});

test('handles report with null details', function (): void {
    $reporter = User::factory()->create();

    $case = resolve(SubmitReport::class)->handle(
        reporter: $reporter,
        contentDTO: makeReportDTO(),
        reason: ViolationType::Nsfw,
        details: null,
        platform: Platform::Discord,
    );

    expect($case->reports->first()->details)->toBeNull();
});

test('dedup increments priority for each additional report', function (): void {
    ModerationRule::query()->create([
        'name' => 'spam trigger',
        'type' => 'keyword',
        'pattern' => 'spam flood',
        'violation_type' => 'spam',
        'severity' => 'critical',
        'action_on_match' => 'ban',
        'is_active' => true,
    ]);

    $dto = makeReportDTO('flood-msg', 'spam flood');
    $action = resolve(SubmitReport::class);

    $case = $action->handle(User::factory()->create(), $dto, ViolationType::Spam, null, Platform::Discord);
    $initialPriority = $case->refresh()->priority;

    $action->handle(User::factory()->create(), $dto, ViolationType::Spam, null, Platform::Discord);

    $case->refresh();
    expect($case->priority)->toBe($initialPriority + 10);
});
