<?php

declare(strict_types=1);

use He4rt\Moderation\Classification\Actions\Classifiers\RuleBasedClassifier;
use He4rt\Moderation\DTOs\ModerationContentDTO;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\Moderation\Rules\ModerationRule;

function contentDTO(string $text): ModerationContentDTO
{
    return new ModerationContentDTO(
        contentId: 'msg-test',
        contentType: 'message',
        sourcePlatform: Platform::Discord,
        authorExternalId: '123',
        textContent: $text,
        mediaUrls: [],
        metadata: [],
        snapshot: ['text' => $text],
    );
}

test('matches case-insensitive keywords', function (): void {
    ModerationRule::query()->create([
        'name' => 'Test', 'type' => 'keyword', 'pattern' => 'spam link',
        'violation_type' => 'spam', 'severity' => 'high', 'action_on_match' => 'warn', 'is_active' => true,
    ]);

    $result = RuleBasedClassifier::make()->classify(contentDTO('Check this SPAM LINK out'));

    expect($result->scores)->toHaveKey('spam')
        ->and($result->scores['spam'])->toBe(0.95);
});

test('matches partial keyword in longer text', function (): void {
    ModerationRule::query()->create([
        'name' => 'Test', 'type' => 'keyword', 'pattern' => 'free money',
        'violation_type' => 'spam', 'severity' => 'medium', 'action_on_match' => 'warn', 'is_active' => true,
    ]);

    $result = RuleBasedClassifier::make()->classify(contentDTO('Hey everyone get free money now at site.com'));

    expect($result->primary)->toBe(ViolationType::Spam);
});

test('matches any keyword from comma-separated list', function (): void {
    ModerationRule::query()->create([
        'name' => 'Slurs', 'type' => 'keyword', 'pattern' => 'word1,word2,word3',
        'violation_type' => 'toxicity', 'severity' => 'critical', 'action_on_match' => 'ban', 'is_active' => true,
    ]);

    $result = RuleBasedClassifier::make()->classify(contentDTO('Something word2 here'));

    expect($result->primary)->toBe(ViolationType::Toxicity)
        ->and($result->severity)->toBe(Severity::Critical);
});

test('does not match when no keywords present', function (): void {
    ModerationRule::query()->create([
        'name' => 'Test', 'type' => 'keyword', 'pattern' => 'bad,evil',
        'violation_type' => 'toxicity', 'severity' => 'low', 'action_on_match' => 'warn', 'is_active' => true,
    ]);

    $result = RuleBasedClassifier::make()->classify(contentDTO('Hello, how are you doing today?'));

    expect($result->scores)->toBeEmpty()
        ->and($result->primary)->toBeNull();
});

test('regex rule matches URL patterns', function (): void {
    ModerationRule::query()->create([
        'name' => 'Scam URLs', 'type' => 'regex', 'pattern' => 'https?://(crypto|nft).*\.(xyz|click)',
        'violation_type' => 'spam', 'severity' => 'high', 'action_on_match' => 'ban', 'is_active' => true,
    ]);

    $result = RuleBasedClassifier::make()->classify(contentDTO('Visit https://crypto-coins.xyz for free'));

    expect($result->primary)->toBe(ViolationType::Spam)
        ->and($result->matchedRules)->toHaveCount(1);
});

test('regex rule does not crash on invalid pattern', function (): void {
    ModerationRule::query()->create([
        'name' => 'Broken', 'type' => 'regex', 'pattern' => '(unclosed[bracket',
        'violation_type' => 'spam', 'severity' => 'low', 'action_on_match' => 'warn', 'is_active' => true,
    ]);

    $result = RuleBasedClassifier::make()->classify(contentDTO('anything'));

    expect($result->scores)->toBeEmpty();
});

test('ignores inactive rules', function (): void {
    ModerationRule::query()->create([
        'name' => 'Disabled', 'type' => 'keyword', 'pattern' => 'hello',
        'violation_type' => 'spam', 'severity' => 'low', 'action_on_match' => 'warn', 'is_active' => false,
    ]);

    $result = RuleBasedClassifier::make()->classify(contentDTO('hello world'));

    expect($result->scores)->toBeEmpty();
});

test('picks highest severity when multiple rules match', function (): void {
    ModerationRule::query()->create([
        'name' => 'Low', 'type' => 'keyword', 'pattern' => 'test',
        'violation_type' => 'spam', 'severity' => 'low', 'action_on_match' => 'warn', 'is_active' => true,
    ]);
    ModerationRule::query()->create([
        'name' => 'Critical', 'type' => 'keyword', 'pattern' => 'test',
        'violation_type' => 'spam', 'severity' => 'critical', 'action_on_match' => 'ban', 'is_active' => true,
    ]);

    $result = RuleBasedClassifier::make()->classify(contentDTO('this is a test'));

    expect($result->severity)->toBe(Severity::Critical)
        ->and($result->matchedRules)->toHaveCount(2);
});

test('returns empty when no rules exist', function (): void {
    $result = RuleBasedClassifier::make()->classify(contentDTO('anything at all'));

    expect($result->scores)->toBeEmpty()
        ->and($result->primary)->toBeNull()
        ->and($result->severity)->toBeNull()
        ->and($result->matchedRules)->toBeEmpty()
        ->and($result->classifierName)->toBe('rules');
});
