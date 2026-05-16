<?php

declare(strict_types=1);

use He4rt\Moderation\Classification\Actions\Classifiers\AggregateClassifier;
use He4rt\Moderation\Classification\Actions\Classifiers\OpenAiClassifier;
use He4rt\Moderation\Classification\Actions\Classifiers\RuleBasedClassifier;
use He4rt\Moderation\Classification\Actions\ContentClassifierContract;
use He4rt\Moderation\DTOs\ClassificationResultDTO;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\Moderation\Rules\ModerationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function makeContentDTO(string $text = 'hello world'): ModerationContentDTO
{
    return new ModerationContentDTO(
        contentId: 'msg-1',
        contentType: 'message',
        sourcePlatform: Platform::Discord,
        authorExternalId: '123',
        textContent: $text,
        mediaUrls: [],
        metadata: [],
        snapshot: ['text' => $text],
        tenantId: null,
    );
}

test('RuleBasedClassifier matches keyword rule', function (): void {
    ModerationRule::query()->create([
        'name' => 'Spam keywords',
        'type' => 'keyword',
        'pattern' => 'buy followers,cheap followers',
        'violation_type' => 'spam',
        'severity' => 'high',
        'action_on_match' => 'warn',
        'is_active' => true,
    ]);

    $classifier = new RuleBasedClassifier();
    $result = $classifier->classify(makeContentDTO('Buy followers now! Best price'));

    expect($result->scores['spam'])->toBeGreaterThanOrEqual(0.9)
        ->and($result->primary)->toBe(ViolationType::Spam)
        ->and($result->matchedRules)->toHaveCount(1);
});

test('RuleBasedClassifier matches regex rule', function (): void {
    ModerationRule::query()->create([
        'name' => 'Crypto scam URLs',
        'type' => 'regex',
        'pattern' => 'https?://(crypto|nft|airdrop).*\.(xyz|click)',
        'violation_type' => 'spam',
        'severity' => 'high',
        'action_on_match' => 'ban',
        'is_active' => true,
    ]);

    $classifier = new RuleBasedClassifier();
    $result = $classifier->classify(makeContentDTO('Check out https://crypto-free.xyz'));

    expect($result->scores['spam'])->toBeGreaterThanOrEqual(0.9)
        ->and($result->matchedRules)->toHaveCount(1);
});

test('RuleBasedClassifier ignores inactive rules', function (): void {
    ModerationRule::query()->create([
        'name' => 'Disabled rule',
        'type' => 'keyword',
        'pattern' => 'hello',
        'violation_type' => 'spam',
        'severity' => 'low',
        'action_on_match' => 'warn',
        'is_active' => false,
    ]);

    $classifier = new RuleBasedClassifier();
    $result = $classifier->classify(makeContentDTO('hello world'));

    expect($result->scores)->toBeEmpty()
        ->and($result->primary)->toBeNull();
});

test('RuleBasedClassifier returns empty for no matches', function (): void {
    $classifier = new RuleBasedClassifier();
    $result = $classifier->classify(makeContentDTO('just a normal message'));

    expect($result->scores)->toBeEmpty()
        ->and($result->primary)->toBeNull()
        ->and($result->matchedRules)->toBeEmpty();
});

test('OpenAiClassifier calls API and returns scores', function (): void {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'results' => [[
                'flagged' => true,
                'categories' => ['harassment' => true, 'hate' => false, 'sexual' => false, 'violence' => false, 'self-harm' => false],
                'category_scores' => ['harassment' => 0.85, 'hate' => 0.12, 'sexual' => 0.01, 'violence' => 0.03, 'self-harm' => 0.0],
            ]],
        ]),
    ]);

    $classifier = new OpenAiClassifier();
    $result = $classifier->classify(makeContentDTO('you are terrible'));

    expect($result->scores)->toHaveKey('harassment')
        ->and($result->scores['harassment'])->toBe(0.85)
        ->and($result->primary)->toBe(ViolationType::Harassment);
});

test('OpenAiClassifier handles API failure gracefully', function (): void {
    Http::fake([
        'api.openai.com/*' => Http::response([], 500),
    ]);

    $classifier = new OpenAiClassifier();
    $result = $classifier->classify(makeContentDTO('test content'));

    expect($result->scores)->toBeEmpty()
        ->and($result->primary)->toBeNull()
        ->and($result->classifierName)->toBe('openai');
});

test('AggregateClassifier merges results from multiple classifiers', function (): void {
    $ruleResult = new ClassificationResultDTO(
        scores: ['spam' => 0.95],
        primary: ViolationType::Spam,
        severity: Severity::High,
        classifierName: 'rules',
        matchedRules: ['rule-1'],
    );

    $aiResult = new ClassificationResultDTO(
        scores: ['spam' => 0.60, 'toxicity' => 0.30],
        primary: ViolationType::Spam,
        severity: Severity::Medium,
        classifierName: 'openai',
        matchedRules: [],
    );

    $mockRule = Mockery::mock(ContentClassifierContract::class);
    $mockRule->shouldReceive('classify')->andReturn($ruleResult);

    $mockAi = Mockery::mock(ContentClassifierContract::class);
    $mockAi->shouldReceive('classify')->andReturn($aiResult);

    $result = AggregateClassifier::make()
        ->addClassifier($mockRule)
        ->addClassifier($mockAi)
        ->classify(makeContentDTO('spam content'));

    expect($result->scores['spam'])->toBe(0.95)
        ->and($result->scores['toxicity'])->toBe(0.30)
        ->and($result->primary)->toBe(ViolationType::Spam)
        ->and($result->severity)->toBe(Severity::High)
        ->and($result->matchedRules)->toBe(['rule-1']);
});
