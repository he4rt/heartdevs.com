<?php

declare(strict_types=1);

use He4rt\Onboarding\Actions\PauseOnboarding;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Exceptions\InvalidOnboardingStatusTransition;
use He4rt\Onboarding\Models\Onboarding;
use He4rt\Onboarding\Models\OnboardingStep;

test('pausing an in_progress onboarding preserves its current step', function (): void {
    $onboarding = Onboarding::factory()->create(['status' => OnboardingStatus::InProgress]);
    $step = OnboardingStep::factory()->for($onboarding, 'onboarding')->create([
        'step_key' => 'form',
        'status' => OnboardingStepStatus::Pending,
    ]);

    $paused = resolve(PauseOnboarding::class)->handle($onboarding);

    expect($paused->status)->toBe(OnboardingStatus::Paused)
        ->and($paused->paused_at)->not->toBeNull()
        ->and($onboarding->fresh()->status)->toBe(OnboardingStatus::Paused)
        ->and($step->fresh()->step_key)->toBe('form')
        ->and($step->fresh()->status)->toBe(OnboardingStepStatus::Pending);
});

test('an onboarding cannot be paused outside the in_progress status', function (OnboardingStatus $from): void {
    $onboarding = Onboarding::factory()->create(['status' => $from]);

    resolve(PauseOnboarding::class)->handle($onboarding);
})->with('non-in_progress statuses')->throws(InvalidOnboardingStatusTransition::class);

dataset('non-in_progress statuses', [
    'paused' => [OnboardingStatus::Paused],
    'completed' => [OnboardingStatus::Completed],
    'rejected' => [OnboardingStatus::Rejected],
]);
