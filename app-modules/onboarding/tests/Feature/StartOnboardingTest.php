<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Onboarding\Actions\StartOnboarding;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Onboarding\Flows\WelcomeOnboardingFlow;
use He4rt\Onboarding\Models\Onboarding;
use He4rt\Onboarding\Models\OnboardingStep;

test('starting Welcome persists the onboarding and its initial form step', function (): void {
    $user = User::factory()->create();

    $onboarding = resolve(StartOnboarding::class)->handle($user, OnboardingType::Welcome);

    expect($onboarding->exists)->toBeTrue()
        ->and($onboarding->type)->toBe(OnboardingType::Welcome)
        ->and($onboarding->status)->toBe(OnboardingStatus::InProgress)
        ->and($onboarding->user_id)->toBe($user->id);

    $step = $onboarding->steps()->sole();

    expect($step->step_key)->toBe('form')
        ->and($step->status)->toBe(OnboardingStepStatus::Pending);
});

test('starting Welcome twice is idempotent and never duplicates rows', function (): void {
    $user = User::factory()->create();

    $first = resolve(StartOnboarding::class)->handle($user, OnboardingType::Welcome);
    $second = resolve(StartOnboarding::class)->handle($user, OnboardingType::Welcome);

    expect($second->id)->toBe($first->id)
        ->and(Onboarding::query()->count())->toBe(1)
        ->and(OnboardingStep::query()->count())->toBe(1);
});

test('OnboardingType::Welcome resolves the WelcomeOnboardingFlow handler', function (): void {
    expect(OnboardingType::Welcome->handler())
        ->toBeInstanceOf(WelcomeOnboardingFlow::class);
});
