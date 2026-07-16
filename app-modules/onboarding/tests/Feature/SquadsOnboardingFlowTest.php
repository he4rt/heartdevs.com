<?php

declare(strict_types=1);

use He4rt\Identity\User\Models\User;
use He4rt\Onboarding\Actions\StartOnboarding;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Onboarding\Flows\SquadsOnboardingFlow;

test('Squads resolves its flow and declares its steps', function (): void {
    $flow = OnboardingType::Squads->handler();

    expect($flow)->toBeInstanceOf(SquadsOnboardingFlow::class)
        ->and($flow->steps())->toBe(['form', 'git_challenge'])
        ->and($flow->prerequisites())->toBeEmpty();
});

test('starting Squads creates an in-progress onboarding with a pending form step', function (): void {
    $user = User::factory()->create();

    $onboarding = resolve(StartOnboarding::class)->handle(
        $user,
        OnboardingType::Squads,
    );

    expect($onboarding->type)->toBe(OnboardingType::Squads)
        ->and($onboarding->status)->toBe(OnboardingStatus::InProgress)
        ->and($onboarding->completed_at)->toBeNull()
        ->and($onboarding->user_id)->toBe($user->id);

    $step = $onboarding->steps()->sole();

    expect($step->step_key)->toBe('form')
        ->and($step->status)->toBe(OnboardingStepStatus::Pending)
        ->and($step->completed_at)->toBeNull();
});

test('advancing the form step leaves the Squads onboarding in progress', function (): void {
    $onboarding = resolve(StartOnboarding::class)->handle(
        User::factory()->create(),
        OnboardingType::Squads,
    );

    $flow = OnboardingType::Squads->handler();

    $flow->advance($onboarding);

    $onboarding->refresh();
    $step = $onboarding->steps()->sole();

    expect($step->status)->toBe(OnboardingStepStatus::Done)
        ->and($step->completed_at)->not->toBeNull()
        ->and($onboarding->status)->toBe(OnboardingStatus::InProgress)
        ->and($onboarding->completed_at)->toBeNull();
});
