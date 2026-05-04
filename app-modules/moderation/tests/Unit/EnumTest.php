<?php

declare(strict_types=1);

use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\AppealStatus;
use He4rt\Moderation\Enums\CaseSource;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;

test('Platform enum has all expected cases', function (): void {
    expect(Platform::cases())->toHaveCount(5)
        ->and(Platform::Discord->value)->toBe('discord')
        ->and(Platform::Twitch->value)->toBe('twitch')
        ->and(Platform::GitHub->value)->toBe('github')
        ->and(Platform::Twitter->value)->toBe('twitter')
        ->and(Platform::Web->value)->toBe('web');
});

test('ActionType enum has all expected cases', function (): void {
    expect(ActionType::cases())->toHaveCount(6)
        ->and(ActionType::Warn->value)->toBe('warn')
        ->and(ActionType::Mute->value)->toBe('mute')
        ->and(ActionType::Kick->value)->toBe('kick')
        ->and(ActionType::Ban->value)->toBe('ban')
        ->and(ActionType::Suspend->value)->toBe('suspend')
        ->and(ActionType::ContentRemove->value)->toBe('content_remove');
});

test('ViolationType enum has all expected cases', function (): void {
    expect(ViolationType::cases())->toHaveCount(7)
        ->and(ViolationType::Spam->value)->toBe('spam')
        ->and(ViolationType::Toxicity->value)->toBe('toxicity')
        ->and(ViolationType::Harassment->value)->toBe('harassment')
        ->and(ViolationType::Nsfw->value)->toBe('nsfw')
        ->and(ViolationType::Raid->value)->toBe('raid')
        ->and(ViolationType::Impersonation->value)->toBe('impersonation')
        ->and(ViolationType::Other->value)->toBe('other');
});

test('CaseStatus enum has all expected cases', function (): void {
    expect(CaseStatus::cases())->toHaveCount(5)
        ->and(CaseStatus::Pending->value)->toBe('pending')
        ->and(CaseStatus::Assigned->value)->toBe('assigned')
        ->and(CaseStatus::Resolved->value)->toBe('resolved')
        ->and(CaseStatus::Escalated->value)->toBe('escalated')
        ->and(CaseStatus::Dismissed->value)->toBe('dismissed');
});

test('CaseSource enum has all expected cases', function (): void {
    expect(CaseSource::cases())->toHaveCount(4)
        ->and(CaseSource::UserReport->value)->toBe('user_report')
        ->and(CaseSource::AutoDetect->value)->toBe('auto_detect')
        ->and(CaseSource::RuleMatch->value)->toBe('rule_match')
        ->and(CaseSource::ManualFlag->value)->toBe('manual_flag');
});

test('AppealStatus enum has all expected cases', function (): void {
    expect(AppealStatus::cases())->toHaveCount(4)
        ->and(AppealStatus::Pending->value)->toBe('pending')
        ->and(AppealStatus::Reviewing->value)->toBe('reviewing')
        ->and(AppealStatus::Upheld->value)->toBe('upheld')
        ->and(AppealStatus::Overturned->value)->toBe('overturned');
});

test('Severity enum has all expected cases', function (): void {
    expect(Severity::cases())->toHaveCount(4)
        ->and(Severity::Low->value)->toBe('low')
        ->and(Severity::Medium->value)->toBe('medium')
        ->and(Severity::High->value)->toBe('high')
        ->and(Severity::Critical->value)->toBe('critical');
});
