<?php

declare(strict_types=1);

use He4rt\Moderation\Rules\ModerationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

test('matches case-insensitive keyword', function (): void {
    $rule = ModerationRule::query()->create([
        'name' => 'Test', 'type' => 'keyword', 'pattern' => 'spam link',
        'violation_type' => 'spam', 'severity' => 'high', 'action_on_match' => 'warn', 'is_active' => true,
    ]);

    expect($rule->matches('Check this SPAM LINK out'))->toBeTrue()
        ->and($rule->matches('nothing here'))->toBeFalse();
});

test('matches any keyword from comma-separated list', function (): void {
    $rule = ModerationRule::query()->create([
        'name' => 'Slurs', 'type' => 'keyword', 'pattern' => 'word1, word2, word3',
        'violation_type' => 'toxicity', 'severity' => 'critical', 'action_on_match' => 'ban', 'is_active' => true,
    ]);

    expect($rule->matches('has word2 in it'))->toBeTrue()
        ->and($rule->matches('no match at all'))->toBeFalse();
});

test('matches regex pattern', function (): void {
    $rule = ModerationRule::query()->create([
        'name' => 'URLs', 'type' => 'regex', 'pattern' => 'https?://(crypto|nft).*\.(xyz|click)',
        'violation_type' => 'spam', 'severity' => 'high', 'action_on_match' => 'ban', 'is_active' => true,
    ]);

    expect($rule->matches('Visit https://crypto-coins.xyz'))->toBeTrue()
        ->and($rule->matches('Visit https://google.com'))->toBeFalse();
});

test('matches regex pattern containing the tilde delimiter', function (string $pattern, string $text): void {
    $rule = ModerationRule::query()->create([
        'name' => 'Tilde', 'type' => 'regex', 'pattern' => $pattern,
        'violation_type' => 'spam', 'severity' => 'high', 'action_on_match' => 'ban', 'is_active' => true,
    ]);

    expect($rule->matches($text))->toBeTrue()
        ->and($rule->matches('https://site.com/user'))->toBeFalse();
})->with([
    'raw tilde' => ['https?://site\.com/~user', 'go to https://site.com/~user now'],
    'escaped tilde' => ['https?://site\.com/\~user', 'go to https://site.com/~user now'],
    'escaped backslash before tilde' => ['site\\\\~user', 'site\\~user'],
]);

test('returns false and logs a warning for invalid regex', function (): void {
    Log::spy();

    $rule = ModerationRule::query()->create([
        'name' => 'Broken', 'type' => 'regex', 'pattern' => '(unclosed[bracket',
        'violation_type' => 'spam', 'severity' => 'low', 'action_on_match' => 'warn', 'is_active' => true,
    ]);

    expect($rule->matches('anything'))->toBeFalse()
        ->and(ModerationRule::regexError('(unclosed[bracket'))->toContain('Compilation failed')
        ->and(ModerationRule::regexError('https?://site\.com/~user'))->toBeNull();

    Log::shouldHaveReceived('warning')->once()->withArgs(
        fn (string $message, array $context): bool => $context['rule_id'] === $rule->id
            && str_contains((string) $context['error'], 'Compilation failed'),
    );
});

test('returns false for unknown rule type', function (): void {
    $rule = ModerationRule::query()->create([
        'name' => 'Unknown', 'type' => 'custom', 'pattern' => 'test',
        'violation_type' => 'spam', 'severity' => 'low', 'action_on_match' => 'warn', 'is_active' => true,
    ]);

    expect($rule->matches('test'))->toBeFalse();
});
