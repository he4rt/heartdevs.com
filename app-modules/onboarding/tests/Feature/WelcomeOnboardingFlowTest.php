<?php

declare(strict_types=1);

use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use He4rt\Onboarding\Actions\StartOnboarding;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Onboarding\Flows\WelcomeOnboardingFlow;

test('advancing the form step completes the Welcome onboarding', function (): void {
    $onboarding = resolve(StartOnboarding::class)->handle(
        Tenant::factory()->create(),
        User::factory()->create(),
        OnboardingType::Welcome,
    );

    resolve(WelcomeOnboardingFlow::class)->advance($onboarding);

    $onboarding->refresh();

    expect($onboarding->status)->toBe(OnboardingStatus::Completed)
        ->and($onboarding->completed_at)->not->toBeNull()
        ->and($onboarding->steps()->sole()->status)->toBe(OnboardingStepStatus::Done);
});

test('Welcome has no prerequisites and a single form step', function (): void {
    $flow = resolve(WelcomeOnboardingFlow::class);

    expect($flow->steps())->toBe(['form'])
        ->and($flow->prerequisites())->toBeEmpty();
});
