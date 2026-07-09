<?php

declare(strict_types=1);

use He4rt\Onboarding\Actions\ResumeOnboarding;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Exceptions\InvalidOnboardingStatusTransition;
use He4rt\Onboarding\Models\Onboarding;
use He4rt\Onboarding\Models\OnboardingStep;

test('resuming a paused onboarding continues from the same step', function (): void {
    $onboarding = Onboarding::factory()->create([
        'status' => OnboardingStatus::Paused,
        'paused_at' => now(),
    ]);
    $step = OnboardingStep::factory()->for($onboarding, 'onboarding')->create([
        'step_key' => 'form',
        'status' => OnboardingStepStatus::Pending,
    ]);

    $resumed = resolve(ResumeOnboarding::class)->handle($onboarding);

    expect($resumed->status)->toBe(OnboardingStatus::InProgress)
        ->and($resumed->paused_at)->toBeNull()
        ->and($onboarding->fresh()->status)->toBe(OnboardingStatus::InProgress)
        ->and($step->fresh()->step_key)->toBe('form')
        ->and($step->fresh()->status)->toBe(OnboardingStepStatus::Pending);
});

test('an onboarding cannot be resumed outside the paused status', function (OnboardingStatus $from): void {
    $onboarding = Onboarding::factory()->create(['status' => $from]);

    resolve(ResumeOnboarding::class)->handle($onboarding);
})->with('non-paused statuses')->throws(InvalidOnboardingStatusTransition::class);

dataset('non-paused statuses', [
    'in_progress' => [OnboardingStatus::InProgress],
    'completed' => [OnboardingStatus::Completed],
    'rejected' => [OnboardingStatus::Rejected],
]);
