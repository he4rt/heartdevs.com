<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\Onboarding\Actions\StartOnboarding;
use He4rt\Onboarding\Enums\OnboardingStatus;
use He4rt\Onboarding\Enums\OnboardingStepStatus;
use He4rt\Onboarding\Enums\OnboardingType;
use He4rt\Onboarding\Exceptions\GateBlockedException;
use He4rt\Onboarding\Flows\SquadsOnboardingFlow;
use He4rt\Onboarding\Models\Onboarding;

test('Squads resolves its flow and declares its steps', function (): void {
    $flow = OnboardingType::Squads->handler();

    expect($flow)->toBeInstanceOf(SquadsOnboardingFlow::class)
        ->and($flow->steps())->toBe(['form', 'git_challenge'])
        ->and($flow->prerequisites())->toBe([OnboardingType::Welcome]);
});

test('starting Squads creates an in-progress onboarding with a pending form step', function (): void {
    $user = User::factory()->create();
    Onboarding::factory()->for($user)->completed()->create();

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
    $user = User::factory()->create();
    Onboarding::factory()->for($user)->completed()->create();

    $onboarding = resolve(StartOnboarding::class)->handle(
        $user,
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

test('gate blocks git_challenge when github is not linked', function (): void {
    $user = User::factory()->create();
    Onboarding::factory()->for($user)->completed()->create();

    $onboarding = resolve(StartOnboarding::class)->handle(
        $user,
        OnboardingType::Squads,
    );

    $flow = OnboardingType::Squads->handler();
    $flow->advance($onboarding);

    expect(fn () => $flow->createNextStep($onboarding))
        ->toThrow(GateBlockedException::class);

    $onboarding->refresh();
    expect($onboarding->steps()->where('step_key', 'git_challenge')->exists())
        ->toBeFalse();
});

test('gate allows git_challenge when github is linked', function (): void {
    $user = User::factory()->create();
    Onboarding::factory()->for($user)->completed()->create();

    $onboarding = resolve(StartOnboarding::class)->handle($user, OnboardingType::Squads);

    // Vincula GitHub
    ExternalIdentity::factory()->create([
        'model_type' => $user->getMorphClass(),
        'model_id' => $user->id,
        'provider' => IdentityProvider::GitHub,
        'connected_at' => now(),
        'disconnected_at' => null,
    ]);

    $flow = OnboardingType::Squads->handler();
    $flow->advance($onboarding);

    $flow->createNextStep($onboarding);

    $onboarding->refresh();
    $step = $onboarding->steps()->where('step_key', 'git_challenge')->sole();

    expect($step->status)->toBe(OnboardingStepStatus::Pending);
});
