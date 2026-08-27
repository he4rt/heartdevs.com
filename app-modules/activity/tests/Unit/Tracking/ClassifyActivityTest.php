<?php

declare(strict_types=1);

use He4rt\Activity\Tracking\Actions\ClassifyActivity;
use He4rt\Activity\Tracking\Enums\ActivityStatus;
use He4rt\Activity\Tracking\Enums\ActivityType;
use He4rt\Activity\Tracking\Enums\ValueTier;

test('classifies high tier activity as pending', function (): void {
    $result = resolve(ClassifyActivity::class)->handle(ActivityType::Article);

    expect($result['tier'])->toBe(ValueTier::High)
        ->and($result['coins_min'])->toBe(100)
        ->and($result['coins_max'])->toBe(300)
        ->and($result['status'])->toBe(ActivityStatus::Pending);
});

test('classifies medium tier activity as auto approved', function (): void {
    $result = resolve(ClassifyActivity::class)->handle(ActivityType::Referral);

    expect($result['tier'])->toBe(ValueTier::Medium)
        ->and($result['coins_min'])->toBe(20)
        ->and($result['coins_max'])->toBe(30)
        ->and($result['status'])->toBe(ActivityStatus::AutoApproved);
});

test('classifies low tier activity as auto approved', function (): void {
    $result = resolve(ClassifyActivity::class)->handle(ActivityType::Engagement);

    expect($result['tier'])->toBe(ValueTier::Low)
        ->and($result['coins_min'])->toBe(1)
        ->and($result['coins_max'])->toBe(3)
        ->and($result['status'])->toBe(ActivityStatus::AutoApproved);
});
