<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Onboarding\Actions\StartOnboarding;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Onboarding\Flows\WelcomeOnboardingFlow;

test('advancing the form step completes the Welcome onboarding', function (): void {
    $onboarding = resolve(StartOnboarding::class)->handle(
        User::factory()->create(),
        OnboardingType::Welcome,
    );

    resolve(WelcomeOnboardingFlow::class)->advance($onboarding);

    $onboarding->refresh();

    expect($onboarding->status)->toBe(OnboardingStatus::Completed)
        ->and($onboarding->completed_at)->not->toBeNull()
        ->and($onboarding->steps()->sole()->status)->toBe(OnboardingStepStatus::Done);
});

test('advancing an already-completed Welcome onboarding preserves the original completed_at', function (): void {
    $onboarding = resolve(StartOnboarding::class)->handle(
        User::factory()->create(),
        OnboardingType::Welcome,
    );

    $flow = resolve(WelcomeOnboardingFlow::class);

    $flow->advance($onboarding);

    $onboarding->refresh();
    $firstCompletedAt = $onboarding->completed_at;

    $flow->advance($onboarding);
    $onboarding->refresh();

    expect($onboarding->status)->toBe(OnboardingStatus::Completed)
        ->and($onboarding->completed_at?->equalTo($firstCompletedAt))->toBeTrue();
});

test('Welcome has no prerequisites and a single form step', function (): void {
    $flow = resolve(WelcomeOnboardingFlow::class);

    expect($flow->steps())->toBe(['form'])
        ->and($flow->prerequisites())->toBeEmpty();
});

test('starting an onboarding type without a handler fails fast and persists nothing', function (): void {
    $user = User::factory()->create();

    expect(fn () => resolve(StartOnboarding::class)->handle($user, OnboardingType::Squads))
        ->toThrow(LogicException::class);

    expect(Onboarding::query()->count())->toBe(0);
});
